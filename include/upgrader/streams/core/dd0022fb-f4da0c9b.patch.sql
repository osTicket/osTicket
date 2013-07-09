/*
 * @version=1.7RC2+
 * 
 * change variable names
 */

-- Canned Responses (with variables)
UPDATE `%TABLE_PREFIX%canned_response` SET `response` = REPLACE(`response`, '%id', '%{ticket.id}');
UPDATE `%TABLE_PREFIX%canned_response` SET `response` = REPLACE(`response`, '%ticket', '%{ticket.number}');
UPDATE `%TABLE_PREFIX%canned_response` SET `response` = REPLACE(`response`, '%name', '%{ticket.name}');
UPDATE `%TABLE_PREFIX%canned_response` SET `response` = REPLACE(`response`, '%email', '%{ticket.email}');
UPDATE `%TABLE_PREFIX%canned_response` SET `response` = REPLACE(`response`, '%subject', '%{ticket.subject}');

UPDATE `%TABLE_PREFIX%canned_response` SET `response` = REPLACE(`response`, '%status', '%{ticket.status}');
UPDATE `%TABLE_PREFIX%canned_response` SET `response` = REPLACE(`response`, '%priority', '%{ticket.priority}');

UPDATE `%TABLE_PREFIX%canned_response` SET `response` = REPLACE(`response`, '%auth', '%{ticket.auth_token}');

UPDATE `%TABLE_PREFIX%canned_response` SET `response` = REPLACE(`response`, '%phone', '%{ticket.phone_number}');
UPDATE `%TABLE_PREFIX%canned_response` SET `response` = REPLACE(`response`, '%createdate', '%{ticket.create_date}');
UPDATE `%TABLE_PREFIX%canned_response` SET `response` = REPLACE(`response`, '%duedate', '%{ticket.due_date}');
UPDATE `%TABLE_PREFIX%canned_response` SET `response` = REPLACE(`response`, '%closedate', '%{ticket.close_date}');

UPDATE `%TABLE_PREFIX%canned_response` SET `response` = REPLACE(`response`, '%topic', '%{ticket.topic.name}');
UPDATE `%TABLE_PREFIX%canned_response` SET `response` = REPLACE(`response`, '%dept', '%{ticket.dept.name}');
UPDATE `%TABLE_PREFIX%canned_response` SET `response` = REPLACE(`response`, '%team', '%{ticket.team.name}');

-- %id
UPDATE `%TABLE_PREFIX%email_template` 
    SET `ticket_autoresp_body` = REPLACE(`ticket_autoresp_body`, '%id', '%{ticket.id}'),
        `message_autoresp_body` = REPLACE(`message_autoresp_body`, '%id', '%{ticket.id}'),
        `ticket_notice_body` = REPLACE(`ticket_notice_body`, '%id', '%{ticket.id}'),
        `ticket_overlimit_body` = REPLACE(`ticket_overlimit_body`, '%id', '%{ticket.id}'),
        `ticket_reply_body` = REPLACE(`ticket_reply_body`, '%id', '%{ticket.id}'),
        `ticket_alert_body` = REPLACE(`ticket_alert_body`, '%id', '%{ticket.id}'),
        `message_alert_body` = REPLACE(`message_alert_body`, '%id', '%{ticket.id}'),
        `note_alert_body` = REPLACE(`note_alert_body`, '%id', '%{ticket.id}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%id', '%{ticket.id}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%id', '%{ticket.id}'),
        `ticket_overdue_body` = REPLACE(`ticket_overdue_body`, '%id', '%{ticket.id}');

-- %ticket
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_autoresp_subj` = REPLACE(`ticket_autoresp_subj`, '%ticket', '%{ticket.number}'),
        `ticket_autoresp_body` = REPLACE(`ticket_autoresp_body`, '%ticket', '%{ticket.number}'),
        `message_autoresp_subj` = REPLACE(`message_autoresp_subj`, '%ticket', '%{ticket.number}'),
        `message_autoresp_body` = REPLACE(`message_autoresp_body`, '%ticket', '%{ticket.number}'),
        `ticket_notice_subj` = REPLACE(`ticket_notice_subj`, '%ticket', '%{ticket.number}'),
        `ticket_notice_body` = REPLACE(`ticket_notice_body`, '%ticket', '%{ticket.number}'),
        `ticket_overlimit_subj` = REPLACE(`ticket_overlimit_subj`, '%ticket', '%{ticket.number}'),
        `ticket_overlimit_body` = REPLACE(`ticket_overlimit_body`, '%ticket', '%{ticket.number}'),
        `ticket_reply_subj` = REPLACE(`ticket_reply_subj`, '%ticket', '%{ticket.number}'),
        `ticket_reply_body` = REPLACE(`ticket_reply_body`, '%ticket', '%{ticket.number}'),
        `ticket_alert_subj` = REPLACE(`ticket_alert_subj`, '%ticket', '%{ticket.number}'),
        `ticket_alert_body` = REPLACE(`ticket_alert_body`, '%ticket', '%{ticket.number}'),
        `message_alert_subj` = REPLACE(`message_alert_subj`, '%ticket', '%{ticket.number}'),
        `message_alert_body` = REPLACE(`message_alert_body`, '%ticket', '%{ticket.number}'),
        `note_alert_subj` = REPLACE(`note_alert_subj`, '%ticket', '%{ticket.number}'),
        `note_alert_body` = REPLACE(`note_alert_body`, '%ticket', '%{ticket.number}'),
        `assigned_alert_subj` = REPLACE(`assigned_alert_subj`, '%ticket', '%{ticket.number}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%ticket', '%{ticket.number}'),
        `transfer_alert_subj` = REPLACE(`transfer_alert_subj`, '%ticket', '%{ticket.number}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%ticket', '%{ticket.number}'),
        `ticket_overdue_subj` = REPLACE(`ticket_overdue_subj`, '%ticket', '%{ticket.number}'),
        `ticket_overdue_body` = REPLACE(`ticket_overdue_body`, '%ticket', '%{ticket.number}');

-- %subject
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_autoresp_subj` = REPLACE(`ticket_autoresp_subj`, '%subject', '%{ticket.subject}'),
        `ticket_autoresp_body` = REPLACE(`ticket_autoresp_body`, '%subject', '%{ticket.subject}'),
        `message_autoresp_subj` = REPLACE(`message_autoresp_subj`, '%subject', '%{ticket.subject}'),
        `message_autoresp_body` = REPLACE(`message_autoresp_body`, '%subject', '%{ticket.subject}'),
        `ticket_notice_subj` = REPLACE(`ticket_notice_subj`, '%subject', '%{ticket.subject}'),
        `ticket_notice_body` = REPLACE(`ticket_notice_body`, '%subject', '%{ticket.subject}'),
        `ticket_overlimit_subj` = REPLACE(`ticket_overlimit_subj`, '%subject', '%{ticket.subject}'),
        `ticket_overlimit_body` = REPLACE(`ticket_overlimit_body`, '%subject', '%{ticket.subject}'),
        `ticket_reply_subj` = REPLACE(`ticket_reply_subj`, '%subject', '%{ticket.subject}'),
        `ticket_reply_body` = REPLACE(`ticket_reply_body`, '%subject', '%{ticket.subject}'),
        `ticket_alert_subj` = REPLACE(`ticket_alert_subj`, '%subject', '%{ticket.subject}'),
        `ticket_alert_body` = REPLACE(`ticket_alert_body`, '%subject', '%{ticket.subject}'),
        `message_alert_subj` = REPLACE(`message_alert_subj`, '%subject', '%{ticket.subject}'),
        `message_alert_body` = REPLACE(`message_alert_body`, '%subject', '%{ticket.subject}'),
        `note_alert_subj` = REPLACE(`note_alert_subj`, '%subject', '%{ticket.subject}'),
        `note_alert_body` = REPLACE(`note_alert_body`, '%subject', '%{ticket.subject}'),
        `assigned_alert_subj` = REPLACE(`assigned_alert_subj`, '%subject', '%{ticket.subject}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%subject', '%{ticket.subject}'),
        `transfer_alert_subj` = REPLACE(`transfer_alert_subj`, '%subject', '%{ticket.subject}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%subject', '%{ticket.subject}'),
        `ticket_overdue_subj` = REPLACE(`ticket_overdue_subj`, '%subject', '%{ticket.subject}'),
        `ticket_overdue_body` = REPLACE(`ticket_overdue_body`, '%subject', '%{ticket.subject}');

-- %name
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_autoresp_subj` = REPLACE(`ticket_autoresp_subj`, '%name', '%{ticket.name}'),
        `ticket_autoresp_body` = REPLACE(`ticket_autoresp_body`, '%name', '%{ticket.name}'),
        `message_autoresp_subj` = REPLACE(`message_autoresp_subj`, '%name', '%{ticket.name}'),
        `message_autoresp_body` = REPLACE(`message_autoresp_body`, '%name', '%{ticket.name}'),
        `ticket_notice_subj` = REPLACE(`ticket_notice_subj`, '%name', '%{ticket.name}'),
        `ticket_notice_body` = REPLACE(`ticket_notice_body`, '%name', '%{ticket.name}'),
        `ticket_overlimit_subj` = REPLACE(`ticket_overlimit_subj`, '%name', '%{ticket.name}'),
        `ticket_overlimit_body` = REPLACE(`ticket_overlimit_body`, '%name', '%{ticket.name}'),
        `ticket_reply_subj` = REPLACE(`ticket_reply_subj`, '%name', '%{ticket.name}'),
        `ticket_reply_body` = REPLACE(`ticket_reply_body`, '%name', '%{ticket.name}'),
        `ticket_alert_subj` = REPLACE(`ticket_alert_subj`, '%name', '%{ticket.name}'),
        `ticket_alert_body` = REPLACE(`ticket_alert_body`, '%name', '%{ticket.name}'),
        `message_alert_subj` = REPLACE(`message_alert_subj`, '%name', '%{ticket.name}'),
        `message_alert_body` = REPLACE(`message_alert_body`, '%name', '%{ticket.name}'),
        `note_alert_subj` = REPLACE(`note_alert_subj`, '%name', '%{ticket.name}'),
        `note_alert_body` = REPLACE(`note_alert_body`, '%name', '%{ticket.name}'),
        `assigned_alert_subj` = REPLACE(`assigned_alert_subj`, '%name', '%{ticket.name}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%name', '%{ticket.name}'),
        `transfer_alert_subj` = REPLACE(`transfer_alert_subj`, '%name', '%{ticket.name}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%name', '%{ticket.name}'),
        `ticket_overdue_subj` = REPLACE(`ticket_overdue_subj`, '%name', '%{ticket.name}'),
        `ticket_overdue_body` = REPLACE(`ticket_overdue_body`, '%name', '%{ticket.name}');

-- %email
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_autoresp_subj` = REPLACE(`ticket_autoresp_subj`, '%email', '%{ticket.email}'),
        `ticket_autoresp_body` = REPLACE(`ticket_autoresp_body`, '%email', '%{ticket.email}'),
        `message_autoresp_subj` = REPLACE(`message_autoresp_subj`, '%email', '%{ticket.email}'),
        `message_autoresp_body` = REPLACE(`message_autoresp_body`, '%email', '%{ticket.email}'),
        `ticket_notice_subj` = REPLACE(`ticket_notice_subj`, '%email', '%{ticket.email}'),
        `ticket_notice_body` = REPLACE(`ticket_notice_body`, '%email', '%{ticket.email}'),
        `ticket_overlimit_subj` = REPLACE(`ticket_overlimit_subj`, '%email', '%{ticket.email}'),
        `ticket_overlimit_body` = REPLACE(`ticket_overlimit_body`, '%email', '%{ticket.email}'),
        `ticket_reply_subj` = REPLACE(`ticket_reply_subj`, '%email', '%{ticket.email}'),
        `ticket_reply_body` = REPLACE(`ticket_reply_body`, '%email', '%{ticket.email}'),
        `ticket_alert_subj` = REPLACE(`ticket_alert_subj`, '%email', '%{ticket.email}'),
        `ticket_alert_body` = REPLACE(`ticket_alert_body`, '%email', '%{ticket.email}'),
        `message_alert_subj` = REPLACE(`message_alert_subj`, '%email', '%{ticket.email}'),
        `message_alert_body` = REPLACE(`message_alert_body`, '%email', '%{ticket.email}'),
        `note_alert_subj` = REPLACE(`note_alert_subj`, '%email', '%{ticket.email}'),
        `note_alert_body` = REPLACE(`note_alert_body`, '%email', '%{ticket.email}'),
        `assigned_alert_subj` = REPLACE(`assigned_alert_subj`, '%email', '%{ticket.email}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%email', '%{ticket.email}'),
        `transfer_alert_subj` = REPLACE(`transfer_alert_subj`, '%email', '%{ticket.email}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%email', '%{ticket.email}'),
        `ticket_overdue_subj` = REPLACE(`ticket_overdue_subj`, '%email', '%{ticket.email}'),
        `ticket_overdue_body` = REPLACE(`ticket_overdue_body`, '%email', '%{ticket.email}');

-- %status
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_autoresp_subj` = REPLACE(`ticket_autoresp_subj`, '%status', '%{ticket.status}'),
        `ticket_autoresp_body` = REPLACE(`ticket_autoresp_body`, '%status', '%{ticket.status}'),
        `message_autoresp_subj` = REPLACE(`message_autoresp_subj`, '%status', '%{ticket.status}'),
        `message_autoresp_body` = REPLACE(`message_autoresp_body`, '%status', '%{ticket.status}'),
        `ticket_notice_subj` = REPLACE(`ticket_notice_subj`, '%status', '%{ticket.status}'),
        `ticket_notice_body` = REPLACE(`ticket_notice_body`, '%status', '%{ticket.status}'),
        `ticket_overlimit_subj` = REPLACE(`ticket_overlimit_subj`, '%status', '%{ticket.status}'),
        `ticket_overlimit_body` = REPLACE(`ticket_overlimit_body`, '%status', '%{ticket.status}'),
        `ticket_reply_subj` = REPLACE(`ticket_reply_subj`, '%status', '%{ticket.status}'),
        `ticket_reply_body` = REPLACE(`ticket_reply_body`, '%status', '%{ticket.status}'),
        `ticket_alert_subj` = REPLACE(`ticket_alert_subj`, '%status', '%{ticket.status}'),
        `ticket_alert_body` = REPLACE(`ticket_alert_body`, '%status', '%{ticket.status}'),
        `message_alert_subj` = REPLACE(`message_alert_subj`, '%status', '%{ticket.status}'),
        `message_alert_body` = REPLACE(`message_alert_body`, '%status', '%{ticket.status}'),
        `note_alert_subj` = REPLACE(`note_alert_subj`, '%status', '%{ticket.status}'),
        `note_alert_body` = REPLACE(`note_alert_body`, '%status', '%{ticket.status}'),
        `assigned_alert_subj` = REPLACE(`assigned_alert_subj`, '%status', '%{ticket.status}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%status', '%{ticket.status}'),
        `transfer_alert_subj` = REPLACE(`transfer_alert_subj`, '%status', '%{ticket.status}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%status', '%{ticket.status}'),
        `ticket_overdue_subj` = REPLACE(`ticket_overdue_subj`, '%status', '%{ticket.status}'),
        `ticket_overdue_body` = REPLACE(`ticket_overdue_body`, '%status', '%{ticket.status}');

-- %priority
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_autoresp_subj` = REPLACE(`ticket_autoresp_subj`, '%priority', '%{ticket.priority}'),
        `ticket_autoresp_body` = REPLACE(`ticket_autoresp_body`, '%priority', '%{ticket.priority}'),
        `message_autoresp_subj` = REPLACE(`message_autoresp_subj`, '%priority', '%{ticket.priority}'),
        `message_autoresp_body` = REPLACE(`message_autoresp_body`, '%priority', '%{ticket.priority}'),
        `ticket_notice_subj` = REPLACE(`ticket_notice_subj`, '%priority', '%{ticket.priority}'),
        `ticket_notice_body` = REPLACE(`ticket_notice_body`, '%priority', '%{ticket.priority}'),
        `ticket_overlimit_subj` = REPLACE(`ticket_overlimit_subj`, '%priority', '%{ticket.priority}'),
        `ticket_overlimit_body` = REPLACE(`ticket_overlimit_body`, '%priority', '%{ticket.priority}'),
        `ticket_reply_subj` = REPLACE(`ticket_reply_subj`, '%priority', '%{ticket.priority}'),
        `ticket_reply_body` = REPLACE(`ticket_reply_body`, '%priority', '%{ticket.priority}'),
        `ticket_alert_subj` = REPLACE(`ticket_alert_subj`, '%priority', '%{ticket.priority}'),
        `ticket_alert_body` = REPLACE(`ticket_alert_body`, '%priority', '%{ticket.priority}'),
        `message_alert_subj` = REPLACE(`message_alert_subj`, '%priority', '%{ticket.priority}'),
        `message_alert_body` = REPLACE(`message_alert_body`, '%priority', '%{ticket.priority}'),
        `note_alert_subj` = REPLACE(`note_alert_subj`, '%priority', '%{ticket.priority}'),
        `note_alert_body` = REPLACE(`note_alert_body`, '%priority', '%{ticket.priority}'),
        `assigned_alert_subj` = REPLACE(`assigned_alert_subj`, '%priority', '%{ticket.priority}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%priority', '%{ticket.priority}'),
        `transfer_alert_subj` = REPLACE(`transfer_alert_subj`, '%priority', '%{ticket.priority}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%priority', '%{ticket.priority}'),
        `ticket_overdue_subj` = REPLACE(`ticket_overdue_subj`, '%priority', '%{ticket.priority}'),
        `ticket_overdue_body` = REPLACE(`ticket_overdue_body`, '%priority', '%{ticket.priority}');

-- %auth
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_autoresp_body` = REPLACE(`ticket_autoresp_body`, '%auth', '%{ticket.auth_code}'),
        `message_autoresp_body` = REPLACE(`message_autoresp_body`, '%auth', '%{ticket.auth_code}'),
        `ticket_notice_body` = REPLACE(`ticket_notice_body`, '%auth', '%{ticket.auth_code}'),
        `ticket_overlimit_body` = REPLACE(`ticket_overlimit_body`, '%auth', '%{ticket.auth_code}'),
        `ticket_reply_body` = REPLACE(`ticket_reply_body`, '%auth', '%{ticket.auth_code}'),
        `ticket_alert_body` = REPLACE(`ticket_alert_body`, '%auth', '%{ticket.auth_code}'),
        `message_alert_body` = REPLACE(`message_alert_body`, '%auth', '%{ticket.auth_code}'),
        `note_alert_body` = REPLACE(`note_alert_body`, '%auth', '%{ticket.auth_code}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%auth', '%{ticket.auth_code}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%auth', '%{ticket.auth_code}'),
        `ticket_overdue_body` = REPLACE(`ticket_overdue_body`, '%auth', '%{ticket.auth_code}');

-- %phone
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_autoresp_body` = REPLACE(`ticket_autoresp_body`, '%phone', '%{ticket.phone_number}'),
        `message_autoresp_body` = REPLACE(`message_autoresp_body`, '%phone', '%{ticket.phone_number}'),
        `ticket_notice_body` = REPLACE(`ticket_notice_body`, '%phone', '%{ticket.phone_number}'),
        `ticket_overlimit_body` = REPLACE(`ticket_overlimit_body`, '%phone', '%{ticket.phone_number}'),
        `ticket_reply_body` = REPLACE(`ticket_reply_body`, '%phone', '%{ticket.phone_number}'),
        `ticket_alert_body` = REPLACE(`ticket_alert_body`, '%phone', '%{ticket.phone_number}'),
        `message_alert_body` = REPLACE(`message_alert_body`, '%phone', '%{ticket.phone_number}'),
        `note_alert_body` = REPLACE(`note_alert_body`, '%phone', '%{ticket.phone_number}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%phone', '%{ticket.phone_number}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%phone', '%{ticket.phone_number}'),
        `ticket_overdue_body` = REPLACE(`ticket_overdue_body`, '%phone', '%{ticket.phone_number}');

-- %createdate
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_autoresp_body` = REPLACE(`ticket_autoresp_body`, '%createdate', '%{ticket.create_date}'),
        `message_autoresp_body` = REPLACE(`message_autoresp_body`, '%createdate', '%{ticket.create_date}'),
        `ticket_notice_body` = REPLACE(`ticket_notice_body`, '%createdate', '%{ticket.create_date}'),
        `ticket_overlimit_body` = REPLACE(`ticket_overlimit_body`, '%createdate', '%{ticket.create_date}'),
        `ticket_reply_body` = REPLACE(`ticket_reply_body`, '%createdate', '%{ticket.create_date}'),
        `ticket_alert_body` = REPLACE(`ticket_alert_body`, '%createdate', '%{ticket.create_date}'),
        `message_alert_body` = REPLACE(`message_alert_body`, '%createdate', '%{ticket.create_date}'),
        `note_alert_body` = REPLACE(`note_alert_body`, '%createdate', '%{ticket.create_date}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%createdate', '%{ticket.create_date}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%createdate', '%{ticket.create_date}'),
        `ticket_overdue_body` = REPLACE(`ticket_overdue_body`, '%createdate', '%{ticket.create_date}');

-- %duedate
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_autoresp_body` = REPLACE(`ticket_autoresp_body`, '%duedate', '%{ticket.due_date}'),
        `message_autoresp_body` = REPLACE(`message_autoresp_body`, '%duedate', '%{ticket.due_date}'),
        `ticket_notice_body` = REPLACE(`ticket_notice_body`, '%duedate', '%{ticket.due_date}'),
        `ticket_overlimit_body` = REPLACE(`ticket_overlimit_body`, '%duedate', '%{ticket.due_date}'),
        `ticket_reply_body` = REPLACE(`ticket_reply_body`, '%duedate', '%{ticket.due_date}'),
        `ticket_alert_body` = REPLACE(`ticket_alert_body`, '%duedate', '%{ticket.due_date}'),
        `message_alert_body` = REPLACE(`message_alert_body`, '%duedate', '%{ticket.due_date}'),
        `note_alert_body` = REPLACE(`note_alert_body`, '%duedate', '%{ticket.due_date}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%duedate', '%{ticket.due_date}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%duedate', '%{ticket.due_date}'),
        `ticket_overdue_body` = REPLACE(`ticket_overdue_body`, '%duedate', '%{ticket.due_date}');

-- %closedate
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_autoresp_body` = REPLACE(`ticket_autoresp_body`, '%closedate', '%{ticket.close_date}'),
        `message_autoresp_body` = REPLACE(`message_autoresp_body`, '%closedate', '%{ticket.close_date}'),
        `ticket_notice_body` = REPLACE(`ticket_notice_body`, '%closedate', '%{ticket.close_date}'),
        `ticket_overlimit_body` = REPLACE(`ticket_overlimit_body`, '%closedate', '%{ticket.close_date}'),
        `ticket_reply_body` = REPLACE(`ticket_reply_body`, '%closedate', '%{ticket.close_date}'),
        `ticket_alert_body` = REPLACE(`ticket_alert_body`, '%closedate', '%{ticket.close_date}'),
        `message_alert_body` = REPLACE(`message_alert_body`, '%closedate', '%{ticket.close_date}'),
        `note_alert_body` = REPLACE(`note_alert_body`, '%closedate', '%{ticket.close_date}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%closedate', '%{ticket.close_date}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%closedate', '%{ticket.close_date}'),
        `ticket_overdue_body` = REPLACE(`ticket_overdue_body`, '%closedate', '%{ticket.close_date}');

-- %topic
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_autoresp_body` = REPLACE(`ticket_autoresp_body`, '%topic', '%{ticket.topic.name}'),
        `message_autoresp_body` = REPLACE(`message_autoresp_body`, '%topic', '%{ticket.topic.name}'),
        `ticket_notice_body` = REPLACE(`ticket_notice_body`, '%topic', '%{ticket.topic.name}'),
        `ticket_overlimit_body` = REPLACE(`ticket_overlimit_body`, '%topic', '%{ticket.topic.name}'),
        `ticket_reply_body` = REPLACE(`ticket_reply_body`, '%topic', '%{ticket.topic.name}'),
        `ticket_alert_body` = REPLACE(`ticket_alert_body`, '%topic', '%{ticket.topic.name}'),
        `message_alert_body` = REPLACE(`message_alert_body`, '%topic', '%{ticket.topic.name}'),
        `note_alert_body` = REPLACE(`note_alert_body`, '%topic', '%{ticket.topic.name}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%topic', '%{ticket.topic.name}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%topic', '%{ticket.topic.name}'),
        `ticket_overdue_body` = REPLACE(`ticket_overdue_body`, '%topic', '%{ticket.topic.name}');

-- %dept
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_autoresp_body` = REPLACE(`ticket_autoresp_body`, '%dept', '%{ticket.dept.name}'),
        `message_autoresp_body` = REPLACE(`message_autoresp_body`, '%dept', '%{ticket.dept.name}'),
        `ticket_notice_body` = REPLACE(`ticket_notice_body`, '%dept', '%{ticket.dept.name}'),
        `ticket_overlimit_body` = REPLACE(`ticket_overlimit_body`, '%dept', '%{ticket.dept.name}'),
        `ticket_reply_body` = REPLACE(`ticket_reply_body`, '%dept', '%{ticket.dept.name}'),
        `ticket_alert_body` = REPLACE(`ticket_alert_body`, '%dept', '%{ticket.dept.name}'),
        `message_alert_body` = REPLACE(`message_alert_body`, '%dept', '%{ticket.dept.name}'),
        `note_alert_body` = REPLACE(`note_alert_body`, '%dept', '%{ticket.dept.name}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%dept', '%{ticket.dept.name}'),
        `transfer_alert_subj` = REPLACE(`transfer_alert_subj`, '%dept', '%{ticket.dept.name}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%dept', '%{ticket.dept.name}'),
        `ticket_overdue_body` = REPLACE(`ticket_overdue_body`, '%dept', '%{ticket.dept.name}');

-- %team
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_autoresp_body` = REPLACE(`ticket_autoresp_body`, '%team', '%{ticket.team.name}'),
        `message_autoresp_body` = REPLACE(`message_autoresp_body`, '%team', '%{ticket.team.name}'),
        `ticket_notice_body` = REPLACE(`ticket_notice_body`, '%team', '%{ticket.team.name}'),
        `ticket_overlimit_body` = REPLACE(`ticket_overlimit_body`, '%team', '%{ticket.team.name}'),
        `ticket_reply_body` = REPLACE(`ticket_reply_body`, '%team', '%{ticket.team.name}'),
        `ticket_alert_body` = REPLACE(`ticket_alert_body`, '%team', '%{ticket.team.name}'),
        `message_alert_body` = REPLACE(`message_alert_body`, '%team', '%{ticket.team.name}'),
        `note_alert_body` = REPLACE(`note_alert_body`, '%team', '%{ticket.team.name}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%team', '%{ticket.team.name}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%team', '%{ticket.team.name}'),
        `ticket_overdue_body` = REPLACE(`ticket_overdue_body`, '%team', '%{ticket.team.name}');

-- %clientlink
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_autoresp_body` = REPLACE(`ticket_autoresp_body`, '%clientlink', '%{ticket.client_link}'),
        `message_autoresp_body` = REPLACE(`message_autoresp_body`, '%clientlink', '%{ticket.client_link}'),
        `ticket_notice_body` = REPLACE(`ticket_notice_body`, '%clientlink', '%{ticket.client_link}'),
        `ticket_overlimit_body` = REPLACE(`ticket_overlimit_body`, '%clientlink', '%{ticket.client_link}'),
        `ticket_reply_body` = REPLACE(`ticket_reply_body`, '%clientlink', '%{ticket.client_link}'),
        `ticket_alert_body` = REPLACE(`ticket_alert_body`, '%clientlink', '%{ticket.client_link}'),
        `message_alert_body` = REPLACE(`message_alert_body`, '%clientlink', '%{ticket.client_link}'),
        `note_alert_body` = REPLACE(`note_alert_body`, '%clientlink', '%{ticket.client_link}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%clientlink', '%{ticket.client_link}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%clientlink', '%{ticket.client_link}'),
        `ticket_overdue_body` = REPLACE(`ticket_overdue_body`, '%clientlink', '%{ticket.client_link}');

-- %staff (recipient of the alert)
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_alert_body` = REPLACE(`ticket_alert_body`, '%staff', '%{recipient}'),
        `message_alert_body` = REPLACE(`message_alert_body`, '%staff', '%{recipient}'),
        `note_alert_body` = REPLACE(`note_alert_body`, '%staff', '%{recipient}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%staff', '%{recipient}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%staff', '%{recipient}'),
        `ticket_overlimit_body` = REPLACE(`ticket_overlimit_body`, '%staff', '%{recipient}'),
        `ticket_overdue_body` = REPLACE(`ticket_overdue_body`, '%staff', '%{recipient}');

-- %message 
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_autoresp_body` = REPLACE(`ticket_autoresp_body`, '%message', '%{message}'),
        `message_autoresp_body` = REPLACE(`message_autoresp_body`, '%message', '%{message}'),
        `ticket_notice_body` = REPLACE(`ticket_notice_body`, '%message', '%{message}'),
        `ticket_overlimit_body` = REPLACE(`ticket_overlimit_body`, '%message', '%{message}'),
        `ticket_reply_body` = REPLACE(`ticket_reply_body`, '%message', '%{message}'),
        `ticket_alert_body` = REPLACE(`ticket_alert_body`, '%message', '%{message}'),
        `message_alert_body` = REPLACE(`message_alert_body`, '%message', '%{message}'),
        `note_alert_body` = REPLACE(`note_alert_body`, '%message', '%{message}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%message', '%{message}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%message', '%{message}'),
        `ticket_overdue_body` = REPLACE(`ticket_overdue_body`, '%message', '%{message}');

-- %response
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_autoresp_body` = REPLACE(`ticket_autoresp_body`, '%response', '%{response}'),
        `message_autoresp_body` = REPLACE(`message_autoresp_body`, '%response', '%{response}'),
        `ticket_notice_body` = REPLACE(`ticket_notice_body`, '%response', '%{response}'),
        `ticket_overlimit_body` = REPLACE(`ticket_overlimit_body`, '%response', '%{response}'),
        `ticket_reply_body` = REPLACE(`ticket_reply_body`, '%response', '%{response}'),
        `ticket_alert_body` = REPLACE(`ticket_alert_body`, '%response', '%{response}'),
        `message_alert_body` = REPLACE(`message_alert_body`, '%response', '%{response}'),
        `note_alert_body` = REPLACE(`note_alert_body`, '%response', '%{response}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%response', '%{response}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%response', '%{response}'),
        `ticket_overdue_body` = REPLACE(`ticket_overdue_body`, '%response', '%{response}');

-- %note 
UPDATE `%TABLE_PREFIX%email_template`
    SET `note_alert_body` = REPLACE(`note_alert_body`, '%note', '* %{note.title} *\r\n\r\n%{note.message}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%note', '%{comments}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%note', '%{comments}');

-- %{note} (dev branch installations)
UPDATE `%TABLE_PREFIX%email_template`
    SET `note_alert_body` = REPLACE(`note_alert_body`, '%{note}', '%{note.message}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%{note}', '%{comments}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%{note}', '%{comments}');

-- %{title} (dev branch installations)
UPDATE `%TABLE_PREFIX%email_template`
    SET `note_alert_body` = REPLACE(`note_alert_body`, '%{title}', '* %{note.title} *\r\n');

-- %url
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_autoresp_body` = REPLACE(`ticket_autoresp_body`, '%url', '%{url}'),
        `message_autoresp_body` = REPLACE(`message_autoresp_body`, '%url', '%{url}'),
        `ticket_notice_body` = REPLACE(`ticket_notice_body`, '%url', '%{url}'),
        `ticket_overlimit_body` = REPLACE(`ticket_overlimit_body`, '%url', '%{url}'),
        `ticket_reply_body` = REPLACE(`ticket_reply_body`, '%url', '%{url}'),
        `ticket_alert_body` = REPLACE(`ticket_alert_body`, '%url', '%{url}'),
        `message_alert_body` = REPLACE(`message_alert_body`, '%url', '%{url}'),
        `note_alert_body` = REPLACE(`note_alert_body`, '%url', '%{url}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%url', '%{url}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%url', '%{url}'),
        `ticket_overdue_body` = REPLACE(`ticket_overdue_body`, '%url', '%{url}');

-- %signature
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_autoresp_body` = REPLACE(`ticket_autoresp_body`, '%signature', '%{signature}'),
        `message_autoresp_body` = REPLACE(`message_autoresp_body`, '%signature', '%{signature}'),
        `ticket_notice_body` = REPLACE(`ticket_notice_body`, '%signature', '%{signature}'),
        `ticket_overlimit_body` = REPLACE(`ticket_overlimit_body`, '%signature', '%{signature}'),
        `ticket_reply_body` = REPLACE(`ticket_reply_body`, '%signature', '%{signature}'),
        `ticket_alert_body` = REPLACE(`ticket_alert_body`, '%signature', '%{signature}'),
        `message_alert_body` = REPLACE(`message_alert_body`, '%signature', '%{signature}'),
        `note_alert_body` = REPLACE(`note_alert_body`, '%signature', '%{signature}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%signature', '%{signature}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%signature', '%{signature}'),
        `ticket_overdue_body` = REPLACE(`ticket_overdue_body`, '%signature', '%{signature}');

-- %assignee
UPDATE `%TABLE_PREFIX%email_template`
    SET `assigned_alert_subj` = REPLACE(`assigned_alert_subj`, '%assignee', '%{assignee}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%assignee', '%{assignee}');

-- %assigner
UPDATE `%TABLE_PREFIX%email_template`
    SET `assigned_alert_subj` = REPLACE(`assigned_alert_subj`, '%assigner', '%{assigner}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%assigner', '%{assigner}');

/* Change links */

-- Client URL -> %{url}/view.php?e=%{ticket.email}&t=%{ticket.number}
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_autoresp_body` = REPLACE(`ticket_autoresp_body`, '%{url}/view.php?e=%{ticket.email}&t=%{ticket.number}', '%{ticket.client_link}'),
        `message_autoresp_body` = REPLACE(`message_autoresp_body`, '%{url}/view.php?e=%{ticket.email}&t=%{ticket.number}', '%{ticket.client_link}'),
        `ticket_notice_body` = REPLACE(`ticket_notice_body`, '%{url}/view.php?e=%{ticket.email}&t=%{ticket.number}', '%{ticket.client_link}'),
        `ticket_overlimit_body` = REPLACE(`ticket_overlimit_body`, '%{url}/view.php?e=%{ticket.email}&t=%{ticket.number}', '%{ticket.client_link}'),
        `ticket_reply_body` = REPLACE(`ticket_reply_body`, '%{url}/view.php?e=%{ticket.email}&t=%{ticket.number}', '%{ticket.client_link}');

-- Client URL -> %{url}/view.php?e=%{ticket.email} (overlimit template)
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_overlimit_body` = REPLACE(`ticket_overlimit_body`, '%{url}/view.php?e=%{ticket.email}', '%{url}/tickets.php?e=%{ticket.email}');

-- Staff URL -> %{url}/scp/ticket.php?id=%{ticket.id} (should be tickets.php)
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_alert_body` = REPLACE(`ticket_alert_body`, '%{url}/scp/ticket.php?id=%{ticket.id}', '%{ticket.staff_link}'),
        `message_alert_body` = REPLACE(`message_alert_body`, '%{url}/scp/ticket.php?id=%{ticket.id}', '%{ticket.staff_link}'),
        `note_alert_body` = REPLACE(`note_alert_body`, '%{url}/scp/ticket.php?id=%{ticket.id}', '%{ticket.staff_link}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%{url}/scp/ticket.php?id=%{ticket.id}', '%{ticket.staff_link}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%{url}/scp/ticket.php?id=%{ticket.id}', '%{ticket.staff_link}'),
        `ticket_overdue_body` = REPLACE(`ticket_overdue_body`, '%{url}/scp/ticket.php?id=%{ticket.id}', '%{ticket.staff_link}');

-- Staff URL 2 -> %{url}/scp/tickets.php?id=%{ticket.id}
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_alert_body` = REPLACE(`ticket_alert_body`, '%{url}/scp/tickets.php?id=%{ticket.id}', '%{ticket.staff_link}'),
        `message_alert_body` = REPLACE(`message_alert_body`, '%{url}/scp/tickets.php?id=%{ticket.id}', '%{ticket.staff_link}'),
        `note_alert_body` = REPLACE(`note_alert_body`, '%{url}/scp/tickets.php?id=%{ticket.id}', '%{ticket.staff_link}'),
        `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%{url}/scp/tickets.php?id=%{ticket.id}', '%{ticket.staff_link}'),
        `transfer_alert_body` = REPLACE(`transfer_alert_body`, '%{url}/scp/tickets.php?id=%{ticket.id}', '%{ticket.staff_link}'),
        `ticket_overdue_body` = REPLACE(`ticket_overdue_body`, '%{url}/scp/tickets.php?id=%{ticket.id}', '%{ticket.staff_link}');

 -- update schema signature
UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='f4da0c9befa257b5a20a923d4e9c0e91';
