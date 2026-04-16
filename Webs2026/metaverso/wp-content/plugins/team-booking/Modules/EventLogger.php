<?php

namespace VSHM\Modules;

use VSHM\Bus\ChangeReservationCustomer;
use VSHM\Bus\ChangeReservationDate;
use VSHM\Bus\ChangeReservationProvider;
use VSHM\Bus\ChangeReservationService;
use VSHM\Bus\ChangeReservationStatus;
use VSHM\Bus\DeleteAllReservations;
use VSHM\Bus\DeleteReservation;
use VSHM\Bus\RegisterPayment;
use VSHM\Bus\ReservationAction;
use VSHM\Bus\SendCustomerBookingCancellationEmail;
use VSHM\Bus\SendCustomerBookingConfirmationEmail;
use VSHM\Bus\SendCustomerBookingReminderEmail;
use VSHM\Bus\SendNotificationEmail;
use VSHM\Bus\UpdateOrCreateReservationProperty;
use VSHM\DB;
use VSHM\REST_Controller;
use VSHM\Tools;


defined('ABSPATH') || exit;

/**
 * Class EventLogger
 *
 * @author VonStroheim
 */
class EventLogger
{
    /**
     * @var string
     */
    public static $route_path = '/logger/';

    public const TABLE_NAME                          = 'tbk_event_logs';
    public const EVENT_FAMILY_CUSTOMER_NOTIFICATIONS = 'customer::notifications';

    public const        TABLE_STRUCTURE = [
        'uid'          => 'text',
        'event_type'   => 'text',
        'user_id'      => ['type' => 'text', 'null' => TRUE],
        'resource_ref' => ['type' => 'text', 'null' => TRUE],
        'auth_token'   => ['type' => 'text', 'null' => TRUE],
        'data_obj'     => 'text',
    ];

    public static function maybe_create_table(): void
    {
        DB::create_table(self::TABLE_NAME, self::TABLE_STRUCTURE);
    }

    /**
     * @param $record
     *
     * @return array
     */
    public static function convert_from_db($record): array
    {
        return [
            'eventType'   => $record['event_type'],
            'userId'      => $record['user_id'],
            'resourceRef' => $record['resource_ref'],
            'authToken'   => $record['auth_token'],
            'created'     => \DateTime::createFromFormat('Y-m-d H:i:s', $record['created'])->getTimestamp(),
            'data'        => json_decode($record['data_obj'], TRUE),
            'id'          => $record['id'],
        ];
    }

    public static function store($data): void
    {
        DB::insert(self::TABLE_NAME, [
            'event_type'   => $data['eventType'],
            'user_id'      => $data['userId'] ?? NULL,
            'resource_ref' => $data['resourceRef'] ?? NULL,
            'auth_token'   => $data['authToken'] ?? NULL,
            'data_obj'     => json_encode($data['data']),
            'created'      => gmdate('Y-m-d H:i:s')
        ]);
    }

    public static function removeByReservationId($reservationId): void
    {
        DB::delete(self::TABLE_NAME, [
            'resource_ref' => $reservationId
        ]);
    }

    public static function bootstrap(): void
    {
        add_action('vshm_bus_dispatched', [self::class, 'storeLog'], 10, 4);
        add_action('vshm_dispatched_DeleteReservation', [self::class, 'post_delete_reservation']);

        REST_Controller::register_routes([
            self::$route_path . 'get/' => [
                'methods'  => \WP_REST_Server::READABLE,
                'callback' => [self::class, 'getLogs'],
                'args'     => [
                    'eventType'   => [
                        'type' => 'string'
                    ],
                    'resourceRef' => [
                        'type' => 'string'
                    ]
                ]
            ],
        ]);
    }

    public static function post_delete_reservation(DeleteReservation $command): void
    {
        self::removeByReservationId($command->getId());
    }

    public static function getLogs(\WP_REST_Request $request): \WP_REST_Response
    {
        /**
         * Grouping by "families"
         */
        $types = '';
        if ($request->get_param('eventType') === self::EVENT_FAMILY_CUSTOMER_NOTIFICATIONS) {
            $types   = [];
            $types[] = DB::whereIn([
                'event_type' => [
                    Tools::get_short_classname(SendCustomerBookingConfirmationEmail::class),
                    Tools::get_short_classname(SendCustomerBookingReminderEmail::class),
                    Tools::get_short_classname(SendCustomerBookingCancellationEmail::class)
                ]
            ]);
            if ($request->get_param('resourceRef')) {
                $types[] = str_replace('WHERE ', '', DB::where(['resource_ref' => $request->get_param('resourceRef')]));
            }
            $types = implode(' AND ', $types);
        } else if (NULL !== $request->get_param('eventType')) {
            $types   = [];
            $types[] = DB::where(['event_type' => $request->get_param('eventType')]);
            if ($request->get_param('resourceRef')) {
                $types[] = str_replace('WHERE ', '', DB::where(['resource_ref' => $request->get_param('resourceRef')]));
            }
            $types = implode(' AND ', $types);
        } else if (NULL !== $request->get_param('resourceRef')) {
            $types = DB::where(['resource_ref' => $request->get_param('resourceRef')]);
        }

        $logs = array_map([self::class, 'convert_from_db'],
            DB::select(self::TABLE_NAME, '*', $types)
        );

        return new \WP_REST_Response(apply_filters('vshm_logger_get_response',
            [
                'status' => 'OK',
                'logs'   => $logs
            ]), 200);
    }

    public static function storeLog($commandName, $command, $agent_type, $agent_id): void
    {
        $data = [];
        $ref  = NULL;

        $authToken = NULL;
        $userId    = NULL;

        if ($agent_type === vshm()->bus::AGENT_USER) {
            $userId = $agent_id ?? get_current_user_id() ?? NULL;
        } else if ($agent_type === vshm()->bus::AGENT_APP) {
            $authToken = $agent_id ?? NULL;
        }

        switch (TRUE) {
            case $command instanceof UpdateOrCreateReservationProperty:
                // No log
                return;
            case $command instanceof SendNotificationEmail:
                $ref = $command->getReservationId();
                break;
            case $command instanceof ChangeReservationProvider:
                $data = [
                    'value' => $command->getProviderId()
                ];
                $ref  = $command->getId();
                break;
            case $command instanceof ChangeReservationDate:
                $data = [
                    'value'     => $command->getUnixTime(),
                    'reference' => $command->getReference()
                ];
                $ref  = $command->getId();
                break;
            case $command instanceof ChangeReservationService:
                $data = [
                    'value' => $command->getServiceId()
                ];
                $ref  = $command->getId();
                break;
            case $command instanceof ChangeReservationStatus:
                $data = [
                    'value' => $command->getStatus()
                ];
                $ref  = $command->getId();
                break;
            case $command instanceof ChangeReservationCustomer:
                $data = [
                    'value' => $command->getCustomerId()
                ];
                $ref  = $command->getId();
                break;
            case $command instanceof ReservationAction:
                $ref = $command->getId();
                break;
            case $command instanceof RegisterPayment:
                $ref  = $command->getGatewayId();
                $data = $command->getReservationsIds();
                break;
            case $command instanceof DeleteAllReservations:
                break;
            default:
                return;
        }
        self::store([
            'eventType'   => $commandName,
            'authToken'   => $authToken,
            'userId'      => $userId,
            'data'        => $data,
            'resourceRef' => $ref
        ]);
    }
}