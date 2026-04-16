<?php

namespace BitCode\BitFormPro\API\Route;

use BitCode\BitFormPro\API\Controller\EntryController;
use BitCode\BitFormPro\API\Controller\NoteController;
use BitCode\BitFormPro\API\Controller\PaymentController;
use WP_REST_Controller;
use WP_REST_Server;

class Routes extends WP_REST_Controller
{
  protected $namespace;

  protected $rest_base;

  protected $entryController;

  protected $noteController;

  protected $paymentController;


  public function __construct()
  {
    $this->namespace = 'bitform';
    $this->rest_base = 'v1';
    $this->entryController = new EntryController();
    $this->noteController = new NoteController();
    $this->paymentController = new PaymentController();
  }

  public function register_routes()
  {
    /* form routes */
    register_rest_route(
      $this->namespace,
      $this->rest_base . '/forms/',
      [
        [
          'methods' => WP_REST_Server::READABLE,
          'callback' => [$this->entryController, 'get_forms'],
          'permission_callback' => [$this, 'get_items_permissions_check'],
        ],
        'schema' => [$this, 'get_item_schema']
      ]
    );
    register_rest_route(
      $this->namespace,
      $this->rest_base . '/fields/(?P<form_id>[\d]+)',
      [
        [
          'methods' => WP_REST_Server::READABLE,
          'callback' => [$this->entryController, 'get_fields'],
          'permission_callback' => [$this, 'get_items_permissions_check']
        ]
      ]
    );
    /**
     * get workflow with form id
     */
    register_rest_route(
      $this->namespace,
      $this->rest_base . '/workflow/(?P<form_id>[\d]+)',
      [
        [
          'methods' => WP_REST_Server::READABLE,
          'callback' => [$this->entryController, 'get_workflow'],
          'permission_callback' => [$this, 'get_items_permissions_check']
        ]
      ]
    );
    /* form routes*/

    /* entry routes*/
    register_rest_route(
      $this->namespace,
      $this->rest_base . '/entry/(?P<form_id>[\d]+)',
      [
        [
          'methods' => WP_REST_Server::CREATABLE,
          'callback' => [$this->entryController, 'entry_store'],
          'permission_callback' => [$this, 'get_items_permissions_check']
        ]
      ]
    );
    register_rest_route(
      $this->namespace,
      $this->rest_base . '/form/response/(?P<id>[\d]+)',
      [
        [
          'methods' => WP_REST_Server::READABLE,
          'callback' => [$this->entryController, 'getEntryResponse'],
          'permission_callback' => [$this, 'get_items_permissions_check']
        ]
      ]
    );
    register_rest_route(
      $this->namespace,
      $this->rest_base . '/entry/(?P<entry_id>[\d]+)',
      [
        [
          'methods' => WP_REST_Server::READABLE,
          'callback' => [$this->entryController, 'entry_view'],
          'permission_callback' => [$this, 'get_items_permissions_check']

        ],
        [
          'methods' => WP_REST_Server::DELETABLE,
          'callback' => [$this->entryController, 'entry_delete'],
          'permission_callback' => [$this, 'get_items_permissions_check']
        ]
      ]
    );

    register_rest_route(
      $this->namespace,
      $this->rest_base . '/entry_update/(?P<entry_id>[\d]+)/',
      [
        [
          'methods' => WP_REST_Server::EDITABLE,
          'callback' => [$this->entryController, 'entry_update'],
          'permission_callback' => [$this, 'get_items_permissions_check']
        ]
      ]
    );
    /* entry routes*/

    /* note routes*/
    register_rest_route(
      $this->namespace,
      $this->rest_base . '/notes/',
      [
        [
          'methods' => WP_REST_Server::READABLE,
          'callback' => [$this->noteController, 'get_notes'],
          'permission_callback' => [$this, 'get_items_permissions_check']
        ]
      ]
    );
    register_rest_route(
      $this->namespace,
      $this->rest_base . '/create-note/',
      [
        [
          'methods' => WP_REST_Server::CREATABLE,
          'callback' => [$this->noteController, 'create_note'],
          'permission_callback' => [$this, 'get_items_permissions_check']
        ]
      ]
    );
    register_rest_route(
      $this->namespace,
      $this->rest_base . '/note/(?P<id>[\d]+)',
      [
        [
          'methods' => WP_REST_Server::READABLE,
          'callback' => [$this->noteController, 'note_edit'],
          'permission_callback' => [$this, 'get_items_permissions_check'],
          'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE),
        ],
        [
          'methods' => WP_REST_Server::EDITABLE,
          'callback' => [$this->noteController, 'note_update'],
          'permission_callback' => [$this, 'get_items_permissions_check']
        ],
        [
          'methods' => WP_REST_Server::DELETABLE,
          'callback' => [$this->noteController, 'note_delete'],
          'permission_callback' => [$this, 'get_items_permissions_check']
        ]
      ]
    );
    /* note routes*/

    /* google sheet route */
    register_rest_route(
      $this->namespace,
      $this->rest_base . '/google/',
      [
        [
          'methods' => WP_REST_Server::READABLE,
          'callback' => [$this->entryController, 'googleAuth'],
          'permission_callback' => '__return_true'
        ]

      ]
    );
    // oneDrive rest route
    register_rest_route(
      $this->namespace,
      $this->rest_base . '/oneDrive/',
      [
        [
          'methods' => WP_REST_Server::READABLE,
          'callback' => [$this->entryController, 'oneDriveAuth'],
          'permission_callback' => '__return_true'
        ]

      ]
    );
    // payment
    register_rest_route(
      $this->namespace,
      $this->rest_base . '/payments/(?P<payment_type>[\w]+)/',
      [
        [
          'methods' => WP_REST_Server::READABLE,
          'callback' => [$this->paymentController, 'handleTransactionCallback'],
          'permission_callback' => '__return_true'
        ],
        [
          'methods' => WP_REST_Server::EDITABLE,
          'callback' => [$this->paymentController, 'handleTransactionCallback'],
          'permission_callback' => '__return_true'
        ]
      ]
    );
    // zoho
    register_rest_route(
      $this->namespace,
      $this->rest_base . '/zoho/',
      [
        [
          'methods' => WP_REST_Server::READABLE,
          'callback' => [$this->entryController, 'authRedirect'],
          'permission_callback' => '__return_true'
        ]

      ]
    );
  }

  public function get_items_permissions_check($request)
  {
    $integrateData = get_option('bitformpro_integrate_key_data');
    $header = $request->get_header('Bitform-Api-Key');
    $api_key = get_option('bitform_secret_api_key');
    $error = '';

    if (empty($header)) {
      $error = ['message' => 'Api Key is required to access this resource'];
    } elseif (!is_array($integrateData) && !isset($integrateData['key'])) {
      $error = 'Bitform pro License Key is Invalid';
    } elseif ($request->get_header('Bitform-Api-Key') !== $api_key || null === $api_key) {
      $error = 'Invalid API key';
    }

    if (!empty($error)) {
      return wp_send_json_error(['message' => $error], 401);
    }

    return true;
  }
}