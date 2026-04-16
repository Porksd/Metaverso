<?php

namespace VSHM;

defined('ABSPATH') || exit;

if (!class_exists(REST_Controller::class)) {

    /**
     * Class REST_Controller
     *
     * @author  VonStroheim
     * @package VSHM
     */
    class REST_Controller
    {
        public const API_VERSION = '1';
        public const NAME_SPACE  = 'thebooking/v';

        /**
         * @return array
         */
        public static function route_args_default(): array
        {
            return [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [],
                'permission_callback' => '__return_true',
                'args'                => []
            ];
        }

        /**
         * @param array $routes
         *
         * @noinspection AdditionOperationOnArraysInspection
         */
        public static function register_routes(array $routes): void
        {
            add_action('rest_api_init', static function () use ($routes) {
                foreach ($routes as $route => $args) {
                    if (Tools::array_is_assoc($args)) {
                        $args += self::route_args_default();
                    } else {
                        foreach ($args as $key => $args_group) {
                            $args[ $key ] += self::route_args_default();
                        }
                    }

                    register_rest_route(self::NAME_SPACE . self::API_VERSION, $route, $args);
                }
            });
        }

        /**
         * @return string
         */
        public static function get_root_rest_url(): string
        {
            return get_rest_url(get_current_blog_id(), self::NAME_SPACE . self::API_VERSION);
        }

        /**
         * @param string $filter_name
         * @param array  $args
         * @param int    $code
         *
         * @return \WP_REST_Response
         */
        public static function get_response(string $filter_name, array $args = [], int $code = 200): \WP_REST_Response
        {
            return new \WP_REST_Response(apply_filters(
                'vshm_' . $filter_name . '_response',
                $args
            ), apply_filters('vshm_' . $filter_name . '_response_code', $code));
        }

        /**
         * @param string $filter_name
         * @param array  $args
         * @param int    $code
         *
         * @return \WP_REST_Response
         */
        public static function get_ok_response(string $filter_name, array $args = [], int $code = 200): \WP_REST_Response
        {
            return new \WP_REST_Response(apply_filters(
                'vshm_' . $filter_name . '_response',
                $args + [
                    'status' => 'OK'
                ]
            ), apply_filters('vshm_' . $filter_name . '_response_code', $code));
        }


        /**
         * @param string $filter_name
         * @param array  $args
         * @param int    $code
         *
         * @return \WP_REST_Response
         */
        public static function get_error_response(string $filter_name, array $args = [], int $code = 400): \WP_REST_Response
        {
            return new \WP_REST_Response(apply_filters(
                'vshm_' . $filter_name . '_response',
                $args + [
                    'status'  => 'KO',
                    'message' => 'Error'
                ]
            ), apply_filters('vshm_' . $filter_name . '_response_code', $code));
        }
    }
}