<?php

function initialize( $hippoDB  )
{
    $res = $hippoDB->query(
        'CREATE TABLE IF NOT EXISTS holidays (
            date DATE NOT NULL PRIMARY KEY
            , description VARCHAR(100) NOT NULL
            , schedule_talk_or_aws ENUM( "YES", "NO" ) DEFAULT "YES"
        )
        ' );

    // Configuration
    $res = $hippoDB->query( "
        CREATE TABLE IF NOT EXISTS config (
            id VARCHAR(100) PRIMARY KEY
            , value VARCHAR(1000) NOT NULL
            , comment TEXT
        )
        "
        );

    // Since deleting is allowed from speaker, id should not AUTO_INCREMENT
    $res = $hippoDB->query(
        'CREATE TABLE IF NOT EXISTS speakers
        (   id INT NOT NULL PRIMARY KEY
            , honorific ENUM( "Dr", "Prof", "Mr", "Ms" ) DEFAULT "Dr"
            , email VARCHAR(100)
            , first_name VARCHAR(100) NOT NULL CHECK( first_name <> "" )
            , middle_name VARCHAR(100)
            , last_name VARCHAR(100)
            , designation VARCHAR(100)
            , department VARCHAR(500)
            , institute VARCHAR(1000) NOT NULL CHECK( institute <> "" )
            , homepage VARCHAR(500)
            , UNIQUE KEY (email,first_name,last_name)
        )' );

    // Other tables.
    $res = $hippoDB->query( "
        CREATE TABLE IF NOT EXISTS logins (
            id VARCHAR( 200 )
            , login VARCHAR(100)
            , email VARCHAR(200)
            , alternative_email VARCHAR(200)
            , first_name VARCHAR(200)
            , last_name VARCHAR(100)
            , roles SET(
                'USER', 'ADMIN', 'MEETINGS', 'ACAD_ADMIN', 'BOOKMYVENUE_ADMIN', 'JC_ADMIN'
            ) DEFAULT 'USER'
            , last_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            , created_on DATETIME
            , joined_on DATE
            , eligible_for_aws ENUM ('YES', 'NO' ) DEFAULT 'NO'
            , valid_until DATE
            , status SET( 'ACTIVE', 'INACTIVE', 'TEMPORARLY_INACTIVE', 'EXPIRED' ) DEFAULT 'ACTIVE'
            , laboffice VARCHAR(200)
            -- The faculty fields here must match in faculty table.
            , title ENUM(
                'FACULTY', 'POSTDOC'
                , 'PHD', 'INTPHD', 'MSC'
                , 'JRF', 'SRF'
                , 'NONACADEMIC_STAFF'
                , 'VISITOR', 'ALUMNI', 'OTHER'
                , 'UNSPECIFIED'
            ) DEFAULT 'UNSPECIFIED'
            , institute VARCHAR(300)
            , PRIMARY KEY (login))"
        );

    $res = $hippoDB->query(
        'CREATE TABLE IF NOT EXISTS talks
        -- id should not be auto_increment.
        ( id INT NOT NULL
            -- This will be prefixed in title of event. e.g. Thesis Seminar by
            -- , Public Lecture by, seminar by etc.
            , class VARCHAR(30) NOT NULL DEFAULT "TALK"
            , speaker VARCHAR(100) NOT NULL -- This can change a little.
            , speaker_id INT NOT NULL
            , host VARCHAR(100) NOT NULL
            , coordinator VARCHAR(100)
            -- Since this has to be unique key, this cannot be very large.
            , title VARCHAR(500) NOT NULL
            , description MEDIUMTEXT
            , created_by VARCHAR(100) NOT NULL
                CHECK( register_by <> "" )
            , created_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            , status ENUM( "CANCELLED", "INVALID", "VALID", "DELIVERED" )
                DEFAULT "VALID"
            , PRIMARY KEY (id)
            , UNIQUE KEY (speaker,title)
        )' );

    // This table holds the email template.
    $res = $hippoDB->query(
        'CREATE TABLE IF NOT EXISTS email_templates
        ( id VARCHAR(100) NOT NULL
        , when_to_send VARCHAR(200)
        , recipients VARCHAR(200) NOT NULL
        , cc VARCHAR(500)
        , description TEXT, PRIMARY KEY (id) )'
        );

    // Save the emails here. A bot should send these emails.
    $res = $hippoDB->query(
        'CREATE TABLE IF NOT EXISTS emails
        ( id INT NOT NULL AUTO_INCREMENT
            , recipients VARCHAR(1000) NOT NULL
            , cc VARCHAR(200)
            , subject VARCHAR(1000) NOT NULL
            , msg TEXT NOT NULL
            , when_to_send DATETIME NOT NULL
            , status ENUM( "PENDING", "SENT", "FAILED", "CANCELLED" ) DEFAULT "PENDING"
            , created_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            , last_tried_on DATETIME
            , PRIMARY KEY (id) )'
        );

    $res = $hippoDB->query( "
        CREATE TABLE IF NOT EXISTS bookmyvenue_requests (
            gid INT NOT NULL
            , rid INT NOT NULL
            , class VARCHAR(30) DEFAULT 'UNKNOWN'
            , created_by VARCHAR(50) NOT NULL
            , title VARCHAR(300) NOT NULL
            , external_id VARCHAR(50)
            , description TEXT
            , venue VARCHAR(80) NOT NULL
            , date DATE NOT NULL
            , start_time TIME NOT NULL
            , end_time TIME NOT NULL
            , status ENUM ( 'PENDING', 'APPROVED', 'REJECTED', 'CANCELLED' ) DEFAULT 'PENDING'
            , is_public_event ENUM( 'YES', 'NO' ) DEFAULT 'NO'
            , url VARCHAR( 1000 )
            , modified_by VARCHAR(50) -- Who modified the request last time.
            , last_modified_on DATETIME
            , timestamp DATETIME
            , PRIMARY KEY (gid, rid)
            , UNIQUE KEY (gid,rid,external_id)
            )
           " );
    $res = $hippoDB->query( "
        -- venues must created before events because events refer to venues key as
        -- foreign key.
        CREATE TABLE IF NOT EXISTS venues (
            id VARCHAR(80) NOT NULL
            , name VARCHAR(300) NOT NULL
            , institute VARCHAR(100) NOT NULL
            , building_name VARCHAR(100) NOT NULL
            , floor INT NOT NULL
            , location VARCHAR(500)
            , type VARCHAR(30) NOT NULL
            , strength INT NOT NULL
            , distance_from_ncbs DECIMAL(3,3) DEFAULT 0.0
            , has_projector ENUM( 'YES', 'NO' ) NOT NULL
            , suitable_for_conference ENUM( 'YES', 'NO' ) NOT NULL
            , has_skype ENUM( 'YES', 'NO' ) DEFAULT 'NO'
            -- How many events this venue have hosted so far. Meaure of popularity.
            , total_events INT NOT NULL DEFAULT 0
            , PRIMARY KEY (id) )"
        );

    // All events are put on this table.
    $res = $hippoDB->query( "
        CREATE TABLE IF NOT EXISTS events (
            -- Sub event will be parent.children format.
            gid INT NOT NULL -- This is group id of events.
            , eid INT NOT NULL -- This is event id in that group.
            -- If some details of this event are stored in some other table, use
            -- this field. Value of this field is formatted as tablename.id e.g.
            -- talks and aws can be referred as talks.3 and annual_work_seminars.5
            -- here.
            , external_id VARCHAR(50)
            -- If yes, this entry will be put on google calendar.
            , is_public_event ENUM( 'YES', 'NO' ) DEFAULT 'NO'
            , class VARCHAR(30) NOT NULL DEFAULT 'UNKNOWN'
            , title VARCHAR(300) NOT NULL
            , description TEXT
            , date DATE NOT NULL
            , venue VARCHAR(80) NOT NULL
            , created_by VARCHAR( 50 ) NOT NULL
            , start_time TIME NOT NULL
            , end_time TIME NOT NULL
            , status ENUM( 'VALID', 'INVALID', 'CANCELLED' ) DEFAULT 'VALID'
            , calendar_id VARCHAR(200)
            , calendar_event_id VARCHAR(200)
            , url VARCHAR(1000)
            , timestamp DATETIME NOT NULL
            , last_modified_on DATETIME
            , PRIMARY KEY ( gid, eid )
            , UNIQUE KEY (gid,eid,external_id)
            )"
        );

    $res = $hippoDB->query( "
        create TABLE IF NOT EXISTS supervisors (
            email VARCHAR(200) PRIMARY KEY NOT NULL
            , first_name VARCHAR( 200 ) NOT NULL
            , middle_name VARCHAR(200)
            , last_name VARCHAR( 200 )
            , affiliation VARCHAR( 1000 ) NOT NULL
            , url VARCHAR(300) ) "
        );

    $res = $hippoDB->query( "
        create TABLE IF NOT EXISTS faculty (
            email VARCHAR(200) PRIMARY KEY NOT NULL
            , first_name VARCHAR( 200 ) NOT NULL
            , middle_name VARCHAR(200)
            , last_name VARCHAR( 200 )
            , affiliation ENUM ( 'NCBS', 'INSTEM', 'OTHER' ) DEFAULT 'INSTEM'
            , status ENUM ( 'ACTIVE', 'INACTIVE', 'INVALID' ) DEFAULT 'ACTIVE'
            , institute VARCHAR( 100 )
            , url VARCHAR(300)
            , specialization VARCHAR(100)
            , created_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            , modified_on DATETIME NOT NULL
            )"
        );

    // This table keeps the archive. We only move complete AWS entry in to this
    // table. Ideally this should not be touched manually.
    $res = $hippoDB->query( "
        create TABLE IF NOT EXISTS annual_work_seminars (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY
            , speaker VARCHAR(200) NOT NULL -- user
            , date DATE NOT NULL -- final date
            , time TIME NOT NULL DEFAULT '16:00'
            , supervisor_1 VARCHAR( 200 ) NOT NULL -- first superviser must be from NCBS
            , supervisor_2 VARCHAR( 200 ) -- superviser 2, optional
            , tcm_member_1 VARCHAR( 200 ) -- Can be null at the time of inserting a query.
            , tcm_member_2 VARCHAR( 200 ) -- optional
            , tcm_member_3 VARCHAR( 200 ) -- optional
            , tcm_member_4 VARCHAR( 200 ) -- optional
            , title VARCHAR( 1000 )
            , abstract MEDIUMTEXT
            , is_presynopsis_seminar ENUM( 'YES', 'NO' ) default 'NO'
            , FOREIGN KEY (speaker) REFERENCES logins(login)
            , UNIQUE KEY (speaker, date)
            , FOREIGN KEY (supervisor_1) REFERENCES faculty(email)
            )"
        );

    $res = $hippoDB->query( "
        create TABLE IF NOT EXISTS upcoming_aws (
            id INT AUTO_INCREMENT PRIMARY KEY
            , speaker VARCHAR(200) NOT NULL -- user
            , date DATE NOT NULL -- tentative date
            , time TIME NOT NULL DEFAULT '16:00'
            , supervisor_1 VARCHAR( 200 )
            , supervisor_2 VARCHAR( 200 )
            , tcm_member_1 VARCHAR( 200 )
            , tcm_member_2 VARCHAR( 200 )
            , tcm_member_3 VARCHAR( 200 )
            , tcm_member_4 VARCHAR( 200 )
            , title VARCHAR( 1000 )
            , abstract MEDIUMTEXT
            , status ENUM( 'VALID', 'INVALID' ) DEFAULT 'VALID'
            , comment TEXT
            , acknowledged ENUM( 'YES', 'NO' ) DEFAULT 'NO'
            , is_presynopsis_seminar ENUM( 'YES', 'NO' ) default 'NO'
            , FOREIGN KEY (speaker) REFERENCES logins(login)
            , UNIQUE (speaker, date) )"
        );

    $res = $hippoDB->query( "
        create TABLE IF NOT EXISTS aws_requests (
            id INT AUTO_INCREMENT PRIMARY KEY
            , speaker VARCHAR(200) NOT NULL -- user
            , date DATE -- final date
            , time TIME DEFAULT '16:00'
            , is_presynopsis_seminar ENUM( 'YES', 'NO' ) DEFAULT 'NO'
            , supervisor_1 VARCHAR( 200 ) -- first superviser must be from NCBS
            , supervisor_2 VARCHAR( 200 ) -- superviser 2, optional
            , tcm_member_1 VARCHAR( 200 ) -- Can be null at the time of inserting a query.
            , tcm_member_2 VARCHAR( 200 ) -- optional
            , tcm_member_3 VARCHAR( 200 ) -- optional
            , tcm_member_4 VARCHAR( 200 ) -- optional
            , scheduled_on DATE
            , title VARCHAR( 1000 )
            , abstract MEDIUMTEXT
            , status ENUM( 'PENDING', 'APPROVED', 'REJECTED', 'INVALID', 'CANCELLED' ) DEFAULT 'PENDING'
            , modidfied_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        );

    // This table keeps request for scheduling AWS. Do not allow deleting a
    // request. It can be rejected or marked expired.
    $res = $hippoDB->query( "
        create TABLE IF NOT EXISTS aws_scheduling_request (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY
            , created_on DATETIME
            , speaker VARCHAR(200) NOT NULL
            , first_preference DATE
            , second_preference DATE
            , reason TEXT NOT NULL
            , status ENUM( 'APPROVED', 'REJECTED', 'PENDING', 'CANCELLED' ) DEFAULT 'PENDING'
            )"
        );

    // Generic table for making some task appear in some time interval.
    $res = $hippoDB->query( "
        CREATE TABLE IF NOT EXISTS conditional_tasks (
            id VARCHAR(50) PRIMARY KEY NOT NULL
            , start_date DATE NOT NULL
            , end_date DATE
            , status ENUM( 'VALID', 'INVALID' ) DEFAULT 'VALID'
            , comment TEXT
        )"
        );
    // This entry keeps track if course registraction should be open/closed for
    // current semester.
    $res = $hippoDB->query( "
        INSERT IGNORE INTO conditional_tasks (id) VALUES ('COURSE_REGISTRATION')
        ");

    // Slots
    $res = $hippoDB->query( "
        create TABLE IF NOT EXISTS slots (
            id VARCHAR(4) NOT NULL
            , groupid INT NOT NULL
            , day ENUM( 'MON','TUE','WED','THU','FRI','SAT') NOT NULL
            , start_time TIME NOT NULL
            , end_time TIME NOT NULL
            , UNIQUE KEY (day,start_time,end_time)
            , PRIMARY KEY (id,groupid)
            )"
        );

    // list of courses.
    $res = $hippoDB->query( "
        create TABLE IF NOT EXISTS courses_metadata  (
            id VARCHAR(8) NOT NULL PRIMARY KEY
            , credits INT NOT NULL DEFAULT 3
            , name VARCHAR(100) NOT NULL
            , description TEXT
            , instructor_1 VARCHAR(50) NOT NULL -- primary instructor.
            , instructor_2 VARCHAR(50)
            , instructor_3 VARCHAR(50)
            , instructor_4 VARCHAR(50)
            , instructor_5 VARCHAR(50)
            , instructor_6 VARCHAR(50)
            , instructor_extras VARCHAR(500) -- comma separated list of extra
            , comment VARCHAR(100)
            )
        ");

    // Instance of running courses.
    $res = $hippoDB->query( "
        create TABLE IF NOT EXISTS courses (
             -- Combination of course code, semester and year
            id VARCHAR(30) PRIMARY KEY
            , semester ENUM( 'AUTUMN', 'SPRING') NOT NULL
            , year VARCHAR(5) NOT NULL
            , course_id VARCHAR(20) NOT NULL
            , start_date DATE NOT NULL
            , end_date DATE NOT NULL
            , venue VARCHAR(20)
            , slot VARCHAR(4)   -- Running in this slot.
            , ignore_tiles VARCHAR(20) -- CSV, ignore these tiles.
            , note VARCHAR(200) DEFAULT ''  -- Add extra comment.
            , UNIQUE KEY(semester,year,course_id)
            )" );



    // Timetable
    $res = $hippoDB->query( "
        create TABLE IF NOT EXISTS course_timetable  (
            course VARCHAR(20) NOT NULL
            , start_date DATE NOT NULL
            , end_date DATE NOT NULL
            , slot VARCHAR(5) NOT NULL
            , PRIMARY KEY (course,start_date)
            ) "
        );

    // course registration.
    $res = $hippoDB->query( "
        create TABLE IF NOT EXISTS course_registration  (
             student_id VARCHAR(50) NOT NULL
            , semester ENUM ( 'AUTUMN', 'SPRING' ) NOT NULL
            , year VARCHAR(5) NOT NULL
            -- CHECK contraints are ignored by MYSQL.
            , course_id VARCHAR(8) NOT NULL CHECK ( course_id <> '' )
            , type ENUM( 'AUDIT', 'CREDIT' ) NOT NULL DEFAULT 'CREDIT'
            , status ENUM ( 'VALID', 'INVALID', 'DROPPED' ) NOT NULL DEFAULT 'VALID'
            , registered_on DATETIME NOT NULL
            , last_modified_on DATETIME
            , grade VARCHAR(2)
            , grade_is_given_on DATETIME  -- Do not show till feedback is given.
            , PRIMARY KEY (student_id,semester,year,course_id)
            ) "
        );

    // DEPRECATED: TOLET.
    $res = $hippoDB->query( "
        CREATE TABLE IF NOT EXISTS apartments (
            id INT PRIMARY KEY
            , type ENUM( 'SHARE', 'SINGLE', '1BHK', '2BHK', '3BHK', '3+BHK' )
            , available_from DATE NOT NULL
            , open_vacancies INT DEFAULT '1'
            , address VARCHAR( 200 ) NOT NULL
            , description MEDIUMTEXT
            , status ENUM( 'AVAILABLE', 'TAKEN', 'WITHDRAWN', 'INVALID', 'EXPIRED' )
                    DEFAULT 'AVAILABLE'
            , owner_contact VARCHAR(200) NOT NULL
            , rent INT NOT NULL
            , advance INT NOT NULL DEFAULT '0'
            , created_by VARCHAR(50) NOT NULL -- Email of owner
            , created_on DATETIME NOT NULL -- timestamp
            , last_modified_on DATETIME
            )"
        );

    $res = $hippoDB->query( "
        CREATE TABLE IF NOT EXISTS apartment_comments (
                id INT PRIMARY KEY AUTO_INCREMENT
                , login VARCHAR(20)
                , apartment_id INT NOT NULL
                , comment VARCHAR(500) NOT NULL
                , timestamp DATETIME
            )"
        );

    $res = $hippoDB->query( "
        CREATE TABLE IF NOT EXISTS alerts (
            login VARCHAR(50) NOT NULL
            , on_table VARCHAR(50) NOT NULL
            , on_field VARCHAR(50) NOT NULL
            , value VARCHAR(50) NOT NULL
            , UNIQUE KEY (login,on_table,on_field,value)
        )"
        );



    $res = $hippoDB->query( "
        CREATE TABLE IF NOT EXISTS upcoming_course_schedule (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY
            , course_id VARCHAR(100) NOT NULL
            , slot VARCHAR(10) NOT NULL
            , venue VARCHAR(50) NOT NULL
            , weight INT DEFAULT 10
            , alloted_slot VARCHAR(10)
            , alloted_venue VARCHAR(50)
            , comment VARCHAR(200)
            , status ENUM( 'VALID', 'INVALID', 'DELETED' ) DEFAULT 'VALID'
            , UNIQUE KEY(course_id,slot,venue)
            )"
        );

    // This table keep journal club subscription
    $res = $hippoDB->query( "
        CREATE TABLE IF NOT EXISTS journal_clubs (
            id VARCHAR(100) NOT NULL PRIMARY KEY
            , title VARCHAR(200) NOT NULL
            , status SET( 'ACTIVE', 'INVALID', 'INACTIVE' ) DEFAULT 'ACTIVE'
            , day ENUM( 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN' ) NOT NULL
            , venue VARCHAR(100) NOT NULL
            , time TIME
            , description TEXT
            , UNIQUE KEY(id,day,time,venue)
            )"
        );

    $res = $hippoDB->query( "
        CREATE TABLE IF NOT EXISTS jc_subscriptions (
            login VARCHAR(100) NOT NULL
            , jc_id VARCHAR(100) NOT NULL
            , status ENUM( 'VALID', 'INVALID', 'UNSUBSCRIBED' ) DEFAULT 'VALID'
            , subscription_type SET( 'NORMAL', 'ADMIN' ) DEFAULT 'NORMAL'
            , last_modified_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            , UNIQUE KEY(login,jc_id)
            )"
        );

    $res = $hippoDB->query( "
        CREATE TABLE IF NOT EXISTS jc_presentations (
            id INT NOT NULL PRIMARY KEY
            , jc_id VARCHAR(100) NOT NULL
            , title VARCHAR(300) NOT NULL
            , presenter VARCHAR(100) NOT NULL -- login from logins table.
            , description TEXT
            , date DATE NOT NULL
            , status ENUM( 'VALID', 'INVALID', 'CANCELLED' ) default 'VALID'
            , url VARCHAR(500) -- URL of paper
            , presentation_url VARCHAR(500) -- URL of presentation
            , UNIQUE KEY(presenter,jc_id,date)
            )"
        );

    // Not put many contraints.
    $res = $hippoDB->query( "
        CREATE TABLE IF NOT EXISTS jc_requests (
            id INT NOT NULL PRIMARY KEY
            , jc_id VARCHAR(100) NOT NULL
            , presenter VARCHAR(100) NOT NULL -- login from logins
            , title VARCHAR(300) NOT NULL
            , description TEXT
            , date DATE NOT NULL
            , url VARCHAR(500) -- Paper URL.
            , status SET( 'VALID', 'INVALID', 'CANCELLED' ) DEFAULT 'VALID'
            , acknowledged ENUM('YES', 'NO') DEFAULT 'YES'
            )"
        );

    $res = $hippoDB->query( "
        CREATE TABLE IF NOT EXISTS votes (
            id VARCHAR(50) NOT NULL  -- table.id
            , voter VARCHAR(20) NOT NULL
            , status SET( 'VALID', 'INVALID', 'CANCELLED' ) DEFAULT 'VALID'
            , voted_on DATE
            , UNIQUE KEY(voter,id)
            )"
        );

    // inventroy
    $res = $hippoDB->query( "
        CREATE TABLE IF NOT EXISTS inventory (
            id INT PRIMARY KEY
            , common_name VARCHAR(50) NOT NULL
            , exact_name VARCHAR(100)
            , vendor VARCHAR(200)
            , quantity_with_unit VARCHAR(40) NOT NULL
            , description TEXT
            , status ENUM( 'VALID', 'INVALID', 'DELETED' ) DEFAULT 'VALID'
            , last_modified_on DATETIME
            , edited_by VARCHAR(100) default 'HIPPO'
            , owner VARCHAR(50) NOT NULL
            , UNIQUE KEY (owner,common_name,vendor,status)
            )"
        );

    // Clickable queries
    $res = $hippoDB->query( "
        CREATE TABLE IF NOT EXISTS queries (
            id INT PRIMARY KEY
            , external_id VARCHAR(50) DEFAULT 'NONE.-1' -- associated table.id in some other table
            , who_can_execute VARCHAR(100) NOT NULL -- which login can execute.
            , query VARCHAR(300) NOT NULL -- query to execute.
            , status ENUM( 'EXECUTED', 'INVALID', 'PENDING' ) DEFAULT 'PENDING'
            , last_modified_on DATETIME
            , edited_by VARCHAR(100) default 'HIPPO'
            , UNIQUE KEY (who_can_execute,query,status)
            )"
        );

    return $res;
}

?>
