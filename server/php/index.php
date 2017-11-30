<?php
/*
 * jQuery File Upload Plugin PHP Example
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * https://opensource.org/licenses/MIT
 */

$options = array(
    			'delete_type' => '',
    			'db_host' => '',
    			'db_user' => '',
    			'db_pass' => '',
    			'db_name' => '',
    			'db_table' => ''
    	);
    	
    	error_reporting(E_ALL | E_STRICT);
    	require('UploadHandler.php');
    	
    	class CustomUploadHandler extends UploadHandler {
    		
    		protected function initialize() {
    			$this->db = new mysqli(
    					$this->options['db_host'],
    					$this->options['db_user'],
    					$this->options['db_pass'],
    					$this->options['db_name']
    					);
    			parent::initialize();
    			$this->db->close();
    		}
    		
    		protected function handle_form_data($file, $index) {
    			$file->title = @$_REQUEST['title'][$index];
    			$file->description = @$_REQUEST['description'][$index];
    			$file->user_id = @$_REQUEST['user_id'][$index];
    			$file->user_id_session = $_SESSION['user_id'];
    		}
    		
    		protected function handle_file_upload($uploaded_file, $name, $size, $type, $error,
    				$index = null, $content_range = null) {
    					$file = parent::handle_file_upload(
    							$uploaded_file, $name, $size, $type, $error, $index, $content_range
    							);
    					if (empty($file->error)) {
    						$sql = 'INSERT INTO `'.$this->options['db_table']
    						.'` (`name`, `size`, `type`, `url`, `title`, `description`,`user_id`)'
    								.' VALUES (?, ?, ?, ?, ?, ?, ?)';
    								$query = $this->db->prepare($sql);
    								$query->bind_param(
    										'sisssss',
    										$file->name,
    										$file->size,
    										$file->type,
    										$file->url,
    										$file->title,
    										$file->description,
    										$file->user_id_session
    										);
    								$query->execute();
    								$file->id = $this->db->insert_id;
    					}
    					return $file;
    		}
    		
protected function set_additional_file_properties($file) {
    			parent::set_additional_file_properties($file);
    			if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    				$user_id = $_SESSION['user_id'];
    				$sql = 'SELECT `id`, `type`, `title`, `description` FROM `'
    						.$this->options['db_table'].'` WHERE `name`=? AND `user_id`=?';
    						$query = $this->db->prepare($sql);
    						$query->bind_param('ss', $file->name, $file->user_id_session);
    						$query->execute();
    						$query->bind_result(
    								$id,
    								$type,
    								$title,
    								$description,
    								$user_id_session
    								);
    						while ($query->fetch()) {
    							$file->id = $id;
    							$file->type = $type;
    							$file->title = $title;
    							$file->description = $description;
    							$file->user_id_session= $user_id_session;
    						}
    			}
    		} 
    		
    		
    		public function delete($print_response = true) {
    			$response = parent::delete(false);
    			foreach ($response as $name => $deleted) {
    				if ($deleted) {
    					$sql = 'DELETE FROM `'
    							.$this->options['db_table'].'` WHERE `name`=?';
    							$query = $this->db->prepare($sql);
    							$query->bind_param('s', $name);
    							$query->execute();
    				}
    			}
    			return $this->generate_response($response, $print_response);
    		}
    		
    	}
    	
    	$upload_handler = new CustomUploadHandler($options);
