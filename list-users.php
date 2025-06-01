<?php

if (!defined('QA_VERSION')) {
    header('Location: ../../');
    exit;
}

require_once QA_INCLUDE_DIR . 'db/selects.php'; 
require_once QA_INCLUDE_DIR . 'db/users.php';
require_once QA_INCLUDE_DIR . 'app/users.php';

class list_users {
	private $role_labels = [
        QA_USER_LEVEL_BASIC     => 'Registered User',
        QA_USER_LEVEL_EXPERT    => 'Expert',
        QA_USER_LEVEL_EDITOR    => 'Editor',
        QA_USER_LEVEL_MODERATOR => 'Moderator',
        QA_USER_LEVEL_ADMIN     => 'Administrator',
        QA_USER_LEVEL_SUPER     => 'Super Administrator',
    ];
	public function admin_form(&$qa_content)
	{
		$saved = false;
		if (qa_clicked('allow_users_save')) {
			qa_opt('list_users_min_level', (int)qa_post_text('list_users_min_level'));

			// Save custom messages for each role level
			foreach (array_keys($this->role_labels) as $level) {
				qa_opt('notice_message_level_' . $level, qa_post_text('notice_message_level_' . $level));
			}
			$saved = true;
		}

		$fields = [];

		// Minimum level dropdown
		$level_select = '<select name="list_users_min_level">';
		foreach ($this->role_labels as $level => $label) {
			$selected = ((int)qa_opt('list_users_min_level') === $level) ? 'selected' : '';
			$level_select .= '<option value="' . $level . '" ' . $selected . '>' . qa_html($label) . '</option>';
		}
		$level_select .= '</select>';

		$fields[] = [
			'label' => 'Minimum user level allowed to access user listing',
			'type'  => 'custom',
			'html'  => $level_select,
		];

		foreach ($this->role_labels as $level => $label) {
			$editorname = qa_opt('editor_for_qs'); // Or 'editor_for_qs', 'editor_for_qs_text', etc.
			$content = qa_opt('notice_message_level_' . $level);
			$editor = qa_load_editor($content, 'html', $editorname);
			$field = qa_editor_load_field($editor, $qa_content, $content, 'html', 'notice_message_level_' . $level, 12, false);
			$field['label'] = 'Custom notice for ' . qa_html($label);
			$fields[] = $field;
		}
		
		return [
			'ok' => $saved ? 'Settings saved' : null,
			'fields' => $fields,
			'buttons' => [
				[
					'label' => 'Save',
					'tags'  => 'name="allow_users_save"',
				],
			],
		];
	}


    public function match_request($request) {
        return ($request === 'list-users');
    }

    public function process_request($request) {
        $qa_content = qa_content_prepare(true);
        $field_errors = [];
        $saved = false;
		
		$min_level_required = (int)qa_opt('list_users_min_level');
		$allowed_user = qa_get_logged_in_level() >= $min_level_required;
	
		if (!$allowed_user) {
			$qa_content['error'] = 'You do not have sufficient privileges to access this page.';
			return $qa_content;
		}


        $selected_level = qa_get('level') !== null ? (int)qa_get('level') : null;
        $start = qa_get_start();
        $page_size = qa_opt('page_size_tag_qs');
		$current_user_level = qa_get_logged_in_level();

        // Handle role change form submission (POST)
        if (qa_post_text('rolechange-form')) {
            $userid = qa_post_text('userid');
            $new_level = (int)qa_post_text('new_level');
			$old_level = qa_db_read_one_value(qa_db_query_sub('SELECT level FROM ^users WHERE userid = #', $userid),true);
			if ($current_user_level != QA_USER_LEVEL_SUPER && $new_level >= $current_user_level) {
				$field_errors['new_level'] = 'You cannot assign a role equal or higher than your own.';
			} else if (!$userid || !isset($this->role_labels[$new_level]) || $new_level===$old_level){
                $field_errors['new_level'] = 'Select a different role to change the role of user.';
            } else if ($current_user_level != QA_USER_LEVEL_SUPER && $old_level>=$current_user_level){
                $field_errors['new_level'] = 'You cannot change the roles of users whose level is equal or higher than yours.';
            }
            else {
				require_once QA_INCLUDE_DIR.'qa-db-notices.php';
                    // Update user level in DB
                    qa_db_query_sub('UPDATE ^users SET level = # WHERE userid = #', $new_level, $userid);
                    $saved = true;
					$old_level_label = $this->role_labels[$selected_level];
					$new_level_label = $this->role_labels[$new_level];
					if ((int)$new_level > (int)$selected_level) {
						$change_type = 'upgraded';
					} elseif ((int)$new_level < (int)$selected_level) {
						$change_type = 'downgraded';
					} else {
						$change_type = 'updated'; // fallback, should not happen if level is actually changed
					}
					$notice_html = '<p>Your role has been <strong>' . $change_type . '</strong> from <strong>' .qa_html($old_level_label) . '</strong> to <strong>' . qa_html($new_level_label) . '</strong>.</p>';
					$custom_message = qa_opt('notice_message_level_' . $new_level);
					if (!empty($custom_message)) {
						$notice_html .= '<div class="role-change-extra">' . $custom_message . '</div>';
					}
					qa_db_usernotice_create($userid, $notice_html, 'html', 'role-change');
            }
        }

        // Dropdown for selecting user level to list
        $level_options = '<select name="level" onchange="this.form.submit()">';
        $level_options .= '<option value="">-- Select user level --</option>';
        foreach ($this->role_labels as $level => $label) {
            $selected = ($selected_level !== null && $selected_level === $level) ? 'selected' : '';
            $level_options .= '<option value="' . $level . '" ' . $selected . '>' . qa_html($label) . '</option>';
        }
        $level_options .= '</select>';

        $qa_content['form'] = [
            'style' => 'wide',
            'tags' => 'method="get" action="' . qa_path_html('list-users') . '"',
            'fields' => [
                [
                    'label' => 'Select the user level',
                    'type'  => 'custom',
                    'html'  => $level_options,
                ]
            ],
        ];

        if ($selected_level !== null) {
            // Fetch users at selected level
            $users = qa_db_read_all_assoc(
                qa_db_query_sub(
                    'SELECT userid, handle, email, level FROM ^users WHERE level = # ORDER BY handle LIMIT #,#',
                    $selected_level, $start, $page_size
                )
            );

            $user_count = qa_db_read_one_value(
                qa_db_query_sub('SELECT COUNT(*) FROM ^users WHERE level = #', $selected_level),
                true
            );

            if (!empty($users)) {
                // Build user list with role-change forms
                $output = '<table class="qa-user-list" style="width:100%; border-collapse: collapse;">';
                $output .= '<thead><tr><th>SNO</th><th>Username</th><th>Email</th><th>Current Role</th><th>Change Role</th><th>Action</th></tr></thead><tbody>';
				$sno=$start+1;
                foreach ($users as $user) {
                    $output .= '<tr style="border-bottom:1px solid #ccc;">';
					$output .= '<td>' . $sno++ . '</td>';
                    $output .= '<td>' . qa_html($user['handle']) . '</td>';
                    $output .= '<td>' . qa_html($user['email']) . '</td>';
                    $output .= '<td>' . qa_html($this->role_labels[$user['level']]) . '</td>';

                    // Role select dropdown (inside a small form)
                    $output .= '<td>';
					if($current_user_level == QA_USER_LEVEL_SUPER || $current_user_level > $selected_level){
						$output .= '<form method="post" action="' . qa_path_html('list-users') . '?level=' . $selected_level . '">';
						$output .= '<input type="hidden" name="userid" value="' . (int)$user['userid'] . '">';
						$output .= '<select name="new_level">';

						$current_user_level = qa_get_logged_in_level();
						foreach ($this->role_labels as $level => $label) {
							// Show only levels less than current user's level
							if ($current_user_level == QA_USER_LEVEL_SUPER || $level < $current_user_level) {
								$selected = ($level === (int)$user['level']) ? 'selected' : '';
								$output .= '<option value="' . $level . '" ' . $selected . '>' . qa_html($label) . '</option>';
							}
						}
						$output .= '</select>';
                    }
					else{
						$output .= '-';
					}
					$output .= '</td>';

                    // Submit button and form close
                    $output .= '<td><button type="submit" name="rolechange-form" value="1">Change Role</button></td>';
                    $output .= '</form>';

                    $output .= '</tr>';
                }
                $output .= '</tbody></table>';
                $qa_content['custom'] = $output;
            } else {
                $qa_content['custom'] = '<p>No users found at that level.</p>';
            }

            $qa_content['page_links'] = qa_html_page_links(
                qa_request(),
                $start,
                $page_size,
                $user_count,
                qa_opt('pages_prev_next'),
                ['level' => $selected_level]
            );
        }

        if ($saved) {
            $qa_content['message_list'] = [
                [
                    'content' => 'User role updated successfully.',
                    'type' => 'success',
                ],
            ];
        } elseif (!empty($field_errors)) {
            $qa_content['error'] = implode('<br>', $field_errors);
        }

        return $qa_content;
    }
}