/*
* Bulk purchase migration js.
*/
(function ($) {
	jQuery( document ).ready(
		function () {
			$( "#ebbp_migrate" ).click(
				function(){
					$( ".ebbp_migrate_notices" ).html( "<span class='ebbp_migrate_notice_msg'>Backing up database ...... </span><br>" );
					jQuery.ajax(
						{
							type: 'POST',
							url: ajaxurl,
							dataType: 'json',
							data: {
								action: 'backup_moodle_enrollment'
							},
							success: function (response) {
								if (response.success ) {
									$( ".ebbp_migrate_notices" ).append( response.msg );
									migrateDatabase();
								} else {
									$( ".ebbp_migrate_notices" ).append( response.msg );
								}
							},
							error: function (error) {
								alert( "Something goes wrong please try again" );
							}
						}
					);

				}
			);

			$( document ).on(
				'click',
				'#ebbp-migrate-backup',
				function () {
					migrateDatabase();
				}
			);

			$( document ).on(
				'click',
				'#ebbp_delete_migrate',
				function () {
					removeMigrationMenu();
				}
			);

			function migrateDatabase(){
				$( ".ebbp_migrate_notices" ).append( "<span class='ebbp_migrate_notice_msg'>Creating Cohorts ...... </span><br>" );

				jQuery.ajax(
					{
						type: 'POST',
						url: ajaxurl,
						dataType: 'json',
						data: {
							action: 'create_cohort'
						},
						success: function (response) {
							if (response.success == true) {
								$( ".ebbp_migrate_notices" ).append( response.data );
								$( ".ebbp_migrate_notices" ).append( "<span class='ebbp_migrate_notice_msg'>Adding user to cohort ...... </span><br>" );
								jQuery.ajax(
									{
										type: 'POST',
										url: ajaxurl,
										dataType: 'json',
										data: {
											action: 'enroll_user_to_cohort'
										},
										success: function (response) {
											if (response.success == true) {
												$( ".ebbp_migrate_notices" ).append( response.data );
											} else {
												$( ".ebbp_migrate_notices" ).append( response.data );
											}
										},
										error: function (error) {
											alert( "Something goes wrong please try again\n" + error );
										}
									}
								);
							} else {
								$( ".ebbp_migrate_notices" ).append( response.data );
							}
						},
						error: function (error) {
							alert( "Something goes wrong please try again" + error );
						}
					}
				);
			}

			function removeMigrationMenu()
			{
				jQuery.ajax(
					{
						type: 'POST',
						url: ajaxurl,
						dataType: 'json',
						data: {
							action: 'remove_migration_submenu'
						},
						success: function (response) {
							if (response.success == true) {
								window.location.href = response.data;
							}
						},
						error: function (error) {
							alert( "Something goes wrong please try again" + error );
						}
					}
				);
			}

		}
	);
})( jQuery );
