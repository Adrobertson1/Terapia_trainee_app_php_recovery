TYPE=VIEW
query=select `ta`.`id` AS `assignment_id`,`ta`.`trainee_id` AS `trainee_id`,concat(`t`.`first_name`,\' \',`t`.`surname`) AS `trainee_name`,`at`.`type_name` AS `type_name`,`ta`.`assigned_date` AS `assigned_date`,`ta`.`due_date` AS `due_date`,`ta`.`status` AS `status`,`u`.`username` AS `assigned_by`,`ta`.`created_at` AS `created_at`,`ta`.`updated_at` AS `updated_at` from (((`trainee_db`.`trainee_assignments` `ta` join `trainee_db`.`trainees` `t` on(`ta`.`trainee_id` = `t`.`trainee_id`)) join `trainee_db`.`assignment_types` `at` on(`ta`.`type_id` = `at`.`type_id`)) left join `trainee_db`.`users` `u` on(`ta`.`assigned_by` = `u`.`user_id`))
md5=b370664c9fd94483f1ec5034f2512f7d
updatable=0
algorithm=0
definer_user=root
definer_host=localhost
suid=1
with_check_option=0
timestamp=0001762537005311717
create-version=2
source=SELECT `ta`.`id` AS `assignment_id`, `ta`.`trainee_id` AS `trainee_id`, concat(`t`.`first_name`,\' \',`t`.`surname`) AS `trainee_name`, `at`.`type_name` AS `type_name`, `ta`.`assigned_date` AS `assigned_date`, `ta`.`due_date` AS `due_date`, `ta`.`status` AS `status`, `u`.`username` AS `assigned_by`, `ta`.`created_at` AS `created_at`, `ta`.`updated_at` AS `updated_at` FROM (((`trainee_assignments` `ta` join `trainees` `t` on(`ta`.`trainee_id` = `t`.`trainee_id`)) join `assignment_types` `at` on(`ta`.`type_id` = `at`.`type_id`)) left join `users` `u` on(`ta`.`assigned_by` = `u`.`user_id`))
client_cs_name=utf8mb4
connection_cl_name=utf8mb4_general_ci
view_body_utf8=select `ta`.`id` AS `assignment_id`,`ta`.`trainee_id` AS `trainee_id`,concat(`t`.`first_name`,\' \',`t`.`surname`) AS `trainee_name`,`at`.`type_name` AS `type_name`,`ta`.`assigned_date` AS `assigned_date`,`ta`.`due_date` AS `due_date`,`ta`.`status` AS `status`,`u`.`username` AS `assigned_by`,`ta`.`created_at` AS `created_at`,`ta`.`updated_at` AS `updated_at` from (((`trainee_db`.`trainee_assignments` `ta` join `trainee_db`.`trainees` `t` on(`ta`.`trainee_id` = `t`.`trainee_id`)) join `trainee_db`.`assignment_types` `at` on(`ta`.`type_id` = `at`.`type_id`)) left join `trainee_db`.`users` `u` on(`ta`.`assigned_by` = `u`.`user_id`))
mariadb-version=100432
