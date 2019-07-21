<?php
require_once BASEPATH.'autoload.php';

class BMVPDO extends PDO
{
    function __construct( $host = 'localhost'  )
    {
        $conf = [
            'mysql' => [
                'host' => '127.0.0.1'
                , 'port' => 3306 
                , 'user' => getenv('HIPPO_DB_USERNAME')
                , 'passwrod' => getenv('HIPPO_DB_PASSWORD')
                , 'database' => 'hippo'
            ]
        ];

        try {
            $conf = parse_ini_file( '/etc/hipporc', $process_section = TRUE );
        } catch (Exception $e) {
            // could  not read for some reason.
            throw("Could not read /etc/hipporc file. Without it, I can not continute...");
        }

        $options = array ( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION );
        $host = $conf['mysql']['host'];
        $port = $conf['mysql']['port'];

        if( $port == -1 )
            $port = 3306;

        $user = $conf['mysql']['user'];
        $password = $conf['mysql']['password'];
        $dbname = $conf['mysql']['database'];

        if( $port == -1 )
            $port = 3306;

        try 
        {
            parent::__construct( 'mysql:host=' . $host . ";dbname=$dbname"
                , $user, $password, $options
            );
        } catch( PDOException $e) {
            echo minionEmbarrassed(
                "failed to connect to database: ".  $e->getMessage()
            );
            $this->error = $e->getMessage( );
            throw $e;
        }

    }

    public function initialize( )
    {
    
        $res = $this->query(
            'CREATE TABLE IF NOT EXISTS holidays (
                date DATE NOT NULL PRIMARY KEY
                , description VARCHAR(100) NOT NULL
                , schedule_talk_or_aws ENUM( "YES", "NO" ) DEFAULT "YES"
            )
            ' );

        // Configuration
        $res = $this->query( "
            CREATE TABLE IF NOT EXISTS config (
                id VARCHAR(100) PRIMARY KEY
                , value VARCHAR(1000) NOT NULL
                , comment TEXT
            )
            "
            );

        // Since deleting is allowed from speaker, id should not AUTO_INCREMENT
        $res = $this->query(
            'CREATE TABLE IF NOT EXISTS speakers
            (   id INT NOT NULL PRIMARY KEY
                , honorific ENUM( "Dr", "Prof", "Mr", "Ms", "" ) DEFAULT "Dr"
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
        $res = $this->query( "
            CREATE TABLE IF NOT EXISTS logins (
                id VARCHAR( 200 )
                , login VARCHAR(100)
                , email VARCHAR(200)
                , alternative_email VARCHAR(200)
                , honorific ENUM( 'Mr', 'Ms', 'Dr', 'Prof', 'Mx', 'Misc', '' ) DEFAULT '' 
                , first_name VARCHAR(200)
                , middle_name VARCHAR(50)
                , last_name VARCHAR(100)
                , roles SET('USER','ADMIN','MEETINGS','ACAD_ADMIN','BOOKMYVENUE_ADMIN','JC_ADMIN','SERVICES_ADMIN') DEFAULT 'USER'
                , designation VARCHAR(40) DEFAULT 'UNKNOWN'
                , last_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                , created_on DATETIME
                , joined_on DATE
                , eligible_for_aws ENUM ('YES', 'NO' ) DEFAULT 'NO'
                , valid_until DATE
                , status SET( 'ACTIVE', 'INACTIVE', 'TEMPORARLY_INACTIVE', 'EXPIRED' ) DEFAULT 'ACTIVE'
                , laboffice VARCHAR(200)
                , pi_or_host VARCHAR(50)
                , specialization VARCHAR(30) 
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

        $res = $this->query(
            'CREATE TABLE IF NOT EXISTS talks
            -- id should not be auto_increment.
            ( id INT NOT NULL
                -- This will be prefixed in title of event. e.g. Thesis Seminar by
                -- , Public Lecture by, seminar by etc.
                , class VARCHAR(30) NOT NULL DEFAULT "TALK"
                , speaker VARCHAR(100) NOT NULL -- This can change a little.
                , speaker_id INT NOT NULL
                , host VARCHAR(100) NOT NULL
                , host_extra VARCHAR(100) -- another host?
                , coordinator VARCHAR(100)
                , title VARCHAR(500) NOT NULL
                , description MEDIUMTEXT
                , created_by VARCHAR(100) NOT NULL
                    CHECK( register_by <> "" )
                , created_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                , status ENUM( "CANCELLED", "INVALID", "VALID", "DELIVERED" ) DEFAULT "VALID"
                , PRIMARY KEY (id)
            )' );

        // This table holds the email template.
        $res = $this->query(
            'CREATE TABLE IF NOT EXISTS email_templates
            ( id VARCHAR(100) NOT NULL
            , when_to_send VARCHAR(200)
            , recipients VARCHAR(200) NOT NULL
            , cc VARCHAR(500)
            , description TEXT, PRIMARY KEY (id) )'
            );

        // Save the emails here. A bot should send these emails.
        $res = $this->query(
            'CREATE TABLE IF NOT EXISTS emails
            (   id INT NOT NULL AUTO_INCREMENT
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

        // Save the list of publications here.
        $res = $this->query(
            'CREATE TABLE IF NOT EXISTS publications
            (   sha512 VARCHAR(129) PRIMARY KEY
                , title VARCHAR(1000) 
                , abstract VARCHAR(4000)  
                , publisher VARCHAR(1000) NOT NULL
                , type VARCHAR(100) 
                , date DATE NOT NULL
                , doi VARCHAR(300)
                , urls VARCHAR(800)
                , source VARCHAR(20) DEFAULT "UNSPECIFIED"
                , external_id VARCHAR(50) -- such as PUBMED
                , metadata_json TEXT
                , modified_on TIMESTAMP default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )'
            );

        $res = $this->query(
            'CREATE TABLE IF NOT EXISTS publication_authors
            (
                author VARCHAR(100) NOT NULL  -- multiple author
                , affiliation VARCHAR(100) NOT NULL  -- multiple author
                , publication_title_sha VARCHAR(129) 
                , publication_title VARCHAR(1000)
                , modified_on TIMESTAMP default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                , UNIQUE KEY (author, publication_title_sha) )'
            );

        $res = $this->query( "
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
                , status ENUM ( 'PENDING', 'APPROVED', 'REJECTED', 'CANCELLED', 'EXPIRED' ) DEFAULT 'PENDING'
                , is_public_event ENUM( 'YES', 'NO' ) DEFAULT 'NO'
                , url VARCHAR( 1000 )
                , modified_by VARCHAR(50) -- Who modified the request last time.
                , last_modified_on DATETIME
                , timestamp DATETIME
                , PRIMARY KEY (gid, rid)
                , UNIQUE KEY (gid,rid,external_id)
                )
               " );

        // Create a table to store user given recurrent pattern. We must keep
        // all entries.
        $res = $this->query( "
                    CREATE TABLE IF NOT EXISTS recurrent_pattern (
                        id INT NOT NULL PRIMARY KEY
                        , request_gid INT UNSIGNED CHECK (request_gid > 0)
                        , pattern VARCHAR(100) NOT NULL
                        , timestamp DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                        , UNIQUE KEY(request_gid, pattern)
                    )
                    ");

        $res = $this->query( "
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
                , latitude DECIMAL(10,8) DEFAULT 0.0
                , longitude DECIMAL(10,8) DEFAULT 0.0
                , has_projector ENUM( 'YES', 'NO' ) NOT NULL
                , suitable_for_conference ENUM( 'YES', 'NO' ) NOT NULL
                , has_skype ENUM( 'YES', 'NO' ) DEFAULT 'NO'
                , allow_booking_on_hippo ENUM('YES', 'NO') DEFAULT 'YES' 
                , quota INT default -1 -- quota minutes per user/week 
                , note_to_user VARCHAR(300)
                -- How many events this venue have hosted so far. Meaure of popularity.
                , total_events INT NOT NULL DEFAULT 0
                , PRIMARY KEY (id) )"
            );

        // All events are put on this table.
        $res = $this->query( "
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

        $res = $this->query( "
            create TABLE IF NOT EXISTS supervisors (
                email VARCHAR(80) PRIMARY KEY NOT NULL
                , first_name VARCHAR( 200 ) NOT NULL
                , middle_name VARCHAR(200)
                , last_name VARCHAR( 200 )
                , affiliation VARCHAR( 1000 ) NOT NULL
                , url VARCHAR(300) ) "
            );

        $res = $this->query( "
            create TABLE IF NOT EXISTS faculty (
                email VARCHAR(80) PRIMARY KEY NOT NULL
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
        $res = $this->query( "
            create TABLE IF NOT EXISTS annual_work_seminars (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY
                , speaker VARCHAR(80) NOT NULL -- user
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
                , venue VARCHAR(50) NOT NULL
                , FOREIGN KEY (speaker) REFERENCES logins(login)
                , UNIQUE KEY (speaker, date, venue)
                )"
            );

        $res = $this->query( "
            create TABLE IF NOT EXISTS upcoming_aws (
                id INT AUTO_INCREMENT PRIMARY KEY
                , speaker VARCHAR(80) NOT NULL -- user
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
                , venue VARCHAR(100) NOT NULL
                , FOREIGN KEY (speaker) REFERENCES logins(login)
                , UNIQUE (speaker, date, venue) )"
            );

        $res = $this->query( "
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
        $res = $this->query( "
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
        $res = $this->query( "
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
        $res = $this->query( "
            INSERT IGNORE INTO conditional_tasks (id) VALUES ('COURSE_REGISTRATION')
            ");

        // Questionnaire
        $res = $this->query( "
            CREATE TABLE IF NOT EXISTS question_bank (
                id INT PRIMARY KEY
                , category VARCHAR(40) NOT NULL
                , subcategory VARCHAR(40)
                , question MEDIUMTEXT NOT NULL 
                , choices VARCHAR(500) 
                , status ENUM('VALID', 'INVALID' ) default 'VALID'
                , last_modified_on DATETIME
                )"
            );

        // Table for question bank related to courses.
        $res = $this->query( "
            CREATE TABLE IF NOT EXISTS course_feedback_questions (
                id INT PRIMARY KEY
                , category VARCHAR(40)
                , question MEDIUMTEXT NOT NULL 
                , choices VARCHAR(200) -- csv for choices. 
                , type ENUM('COURSE SPECIFIC', 'INSTRUCTOR SPECIFIC' ) DEFAULT 'COURSE SPECIFIC'
                , status ENUM('VALID', 'INVALID' ) default 'VALID'
                , last_modified_on DATETIME
                )"
            );

        // table to record course feedback.
        $res = $this->query( "
            CREATE TABLE IF NOT EXISTS course_feedback_responses (
                login VARCHAR(40) NOT NULL
                , question_id INT NOT NULL
                , course_id VARCHAR(30) NOT NULL
                , semester VARCHAR(20) NOT NULL
                , year VARCHAR(5) NOT NULL -- will last till year 99999
                , instructor_email VARCHAR(40) NOT NULL DEFAULT '' -- for instructor specfic questions.
                , response VARCHAR(1000) NOT NULL -- it could be large text but no longer than 1000 char.
                , status ENUM('VALID', 'INVALID', 'WITHDRAWN' ) default 'VALID'
                , weight INT(3) UNSIGNED NOT NULL DEFAULT '1' -- weight of this question.
                , timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                , last_modified_on DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                , UNIQUE KEY(login,question_id,course_id,instructor_email)
                )"
            );


        // table to record poll.
        $res = $this->query( "
            CREATE TABLE IF NOT EXISTS poll_response (
                login VARCHAR(40) NOT NULL
                , question_id INT NOT NULL
                , external_id VARCHAR(100) NOT NULL -- e.g. COURSEID.SEMESTER.YEAR
                , response VARCHAR(1000) NOT NULL -- it could be large text but no longer than 1000 char.
                , status ENUM('VALID', 'INVALID', 'WITHDRAWN' ) default 'VALID'
                , timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                , last_modified_on DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                , UNIQUE KEY(login,question_id,external_id)
                )"
            );

        // table to record noticeboard.
        $res = $this->query( "
            CREATE TABLE IF NOT EXISTS notice_board (
                id INT NOT NULL
                , login VARCHAR(40) NOT NULL
                , external_id VARCHAR(50) NOT NULL -- e.g. JCID etc.
                , message TEXT
                , status ENUM('VALID', 'INVALID', 'EXPIRED' ) default 'VALID'
                , timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                , last_modified_on DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )"
            );

        // Slots
        $res = $this->query( "
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
        $res = $this->query( "
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

        // Instance of currently running courses.
        $res = $this->query( "
            create TABLE IF NOT EXISTS courses (
                 -- Combination of course code, semester and year
                id VARCHAR(30) PRIMARY KEY
                , semester VARCHAR(30) NOT NULL -- usually AUTUMN and SPRING
                , year YEAR NOT NULL
                , course_id VARCHAR(20) NOT NULL
                , start_date DATE NOT NULL
                , end_date DATE NOT NULL
                , max_registration INT DEFAULT -1
                , allow_deregistration_until DATE -- Usually 30 days from the starting of the course.
                , is_audit_allowed ENUM('YES', 'NO') default 'YES'
                , venue VARCHAR(20)
                , slot VARCHAR(4)   -- Running in this slot.
                , ignore_tiles VARCHAR(20) -- CSV, ignore these tiles.
                , note VARCHAR(200) DEFAULT ''  -- Add extra comment.
                , UNIQUE KEY(semester,year,course_id)
                )" );



        // Timetable
        $res = $this->query( "
            create TABLE IF NOT EXISTS course_timetable  (
                course VARCHAR(20) NOT NULL
                , start_date DATE NOT NULL
                , end_date DATE NOT NULL
                , slot VARCHAR(5) NOT NULL
                , PRIMARY KEY (course,start_date)
                ) "
            );

        // course registration.
        $res = $this->query( "
            create TABLE IF NOT EXISTS course_registration  (
                 student_id VARCHAR(50) NOT NULL
                , semester ENUM ( 'AUTUMN', 'SPRING' ) NOT NULL
                , year VARCHAR(5) NOT NULL
                -- CHECK contraints are ignored by MYSQL.
                , course_id VARCHAR(8) NOT NULL CHECK ( course_id <> '' )
                , type ENUM( 'AUDIT', 'CREDIT' ) NOT NULL DEFAULT 'CREDIT'
                , status ENUM ( 'VALID', 'INVALID', 'WAITLIST', 'DROPPED' ) NOT NULL DEFAULT 'VALID'
                , registered_on DATETIME NOT NULL
                , last_modified_on DATETIME
                , grade VARCHAR(2)
                , grade_is_given_on DATETIME  -- Do not show till feedback is given.
                , PRIMARY KEY (student_id,semester,year,course_id)
                ) "
            );

        // This is available on Hippo App only.
        $res = $this->query( "
            CREATE TABLE IF NOT EXISTS accomodation (
                id INT PRIMARY KEY
                , type ENUM( 'SHARED ROOM', 'SINGLE ROOM', '1BHK', '2BHK', '3BHK', '3+BHK' )
                , available_from DATE NOT NULL
                , open_vacancies INT DEFAULT '1'
                , address VARCHAR(200) NOT NULL
                , available_for ENUM('MALE ONLY', 'FEMALE ONLY', 'MALE & FEMALE') NOT NULL
                , description MEDIUMTEXT
                , status ENUM('AVAILABLE', 'TAKEN', 'WITHDRAWN', 'INVALID', 'EXPIRED') DEFAULT 'AVAILABLE'
                , owner_contact VARCHAR(200) NOT NULL
                , rent INT NOT NULL
                , extra VARCHAR(100) -- e.g., electricity, maintainence
                , advance INT DEFAULT '0'
                , url VARCHAR(100) -- url to gallery
                , created_by VARCHAR(50) NOT NULL -- 
                , created_on DATETIME NOT NULL -- timestamp
                , last_modified_on DATETIME ON UPDATE CURRENT_TIMESTAMP
                )"
            );

        $res = $this->query( "
            CREATE TABLE IF NOT EXISTS comment (
                    id INT PRIMARY KEY
                    , commenter VARCHAR(20)
                    , external_id VARCHAR(50) NOT NULL
                    , comment VARCHAR(256) NOT NULL
                    , created_on DATETIME DEFAULT CURRENT_TIMESTAMP
                    , last_modified_on DATETIME ON UPDATE CURRENT_TIMESTAMP
                    , status ENUM('VALID', 'INVALID', 'DELETED', 'ABUSIVE') DEFAULT 'VALID'
                    , UNIQUE KEY(external_id,commenter)
                )"
            );

        $res = $this->query( "
            CREATE TABLE IF NOT EXISTS alerts (
                login VARCHAR(50) NOT NULL
                , on_table VARCHAR(50) NOT NULL
                , on_field VARCHAR(50) NOT NULL
                , value VARCHAR(50) NOT NULL
                , UNIQUE KEY (login,on_table,on_field,value)
            )"
            );



        $res = $this->query( "
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
        $res = $this->query( "
            CREATE TABLE IF NOT EXISTS journal_clubs (
                id VARCHAR(100) NOT NULL PRIMARY KEY
                , title VARCHAR(200) NOT NULL
                , status SET( 'ACTIVE', 'INVALID', 'INACTIVE' ) DEFAULT 'ACTIVE'
                , day ENUM( 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN' ) NOT NULL
                , venue VARCHAR(100) NOT NULL
                , time TIME
                , description TEXT
                , scheduling_method ENUM( 'DONT SCHEDULE', 'RANDOM', 'ALPHABETICAL', 'DAYS_ON_CAMPUS', 'NUM_JC_SO_FAR' 
                    ) DEFAULT 'RANDOM'
                , send_email_on_days VARCHAR(30) -- csv list otherwise 3 days before the day
                , UNIQUE KEY(id,day,time,venue)
                )"
            );

        $res = $this->query( "
            CREATE TABLE IF NOT EXISTS jc_subscriptions (
                login VARCHAR(100) NOT NULL
                , jc_id VARCHAR(100) NOT NULL
                , status ENUM( 'VALID', 'INVALID', 'UNSUBSCRIBED' ) DEFAULT 'VALID'
                , subscription_type SET( 'NORMAL', 'ADMIN' ) DEFAULT 'NORMAL'
                , last_modified_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                , UNIQUE KEY(login,jc_id)
                )"
            );

        // JC presentations.
        $res = $this->query( "
            CREATE TABLE IF NOT EXISTS jc_presentations (
                id INT NOT NULL PRIMARY KEY
                , jc_id VARCHAR(100) NOT NULL
                , title VARCHAR(300) NOT NULL
                , presenter VARCHAR(100) NOT NULL 
                , other_presenters VARCHAR(300) -- list of other presenters.
                , description TEXT
                , date DATE NOT NULL
                , time TIME NOT NULL -- usually the default time from journal_club.
                , venue VARCHAR(30) NOT NULL -- usually the default venue from journal_clubs
                , status ENUM( 'VALID', 'INVALID', 'CANCELLED' ) default 'VALID'
                , url VARCHAR(500) -- URL of paper
                , presentation_url VARCHAR(500) -- URL of presentation
                , UNIQUE KEY(presenter,jc_id,date)
                )"
            );

        // JC requests.
        $res = $this->query( "
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

        $res = $this->query( "
            CREATE TABLE IF NOT EXISTS votes (
                id VARCHAR(50) NOT NULL  -- table.id
                , voter VARCHAR(20) NOT NULL
                , status SET( 'VALID', 'INVALID', 'CANCELLED' ) DEFAULT 'VALID'
                , voted_on DATE
                , UNIQUE KEY(voter,id)
                )"
            );

        // inventroy
        $res = $this->query( "
            CREATE TABLE IF NOT EXISTS inventory (
                id INT PRIMARY KEY
                , name VARCHAR(50) NOT NULL
                , scientific_name VARCHAR(100)
                , vendor VARCHAR(100) NOT NULL default 'UNSPECIFIED'
                , quantity_with_unit VARCHAR(40) NOT NULL default '1 nos'
                , location VARCHAR(200) DEFAULT 'UNSPECIFIED'
                , description TEXT
                , status ENUM( 'VALID', 'INVALID', 'DELETED' ) DEFAULT 'VALID'
                , item_condition ENUM('FUNCTIONAL', 'DSYFUNCTIONAL'
                        , 'LOST', 'EXPIRED', 'UNKNOWN') default 'FUNCTIONAL'
                , expiry_date DATETIME   -- send reminder 1 month in advance.
                , last_modified_on DATETIME
                , edited_by VARCHAR(100) default 'HIPPO'
                , person_in_charge VARCHAR(200) NOT NULL -- could be multiple.
                , faculty_in_charge VARCHAR(100) NOT NULL -- could be multiple faculty 
                , requires_booking ENUM('YES', 'NO') default 'NO'
                , UNIQUE KEY (faculty_in_charge, name)
                )"
            );

        // Equipements booking.
        $res = $this->query( "
            CREATE TABLE IF NOT EXISTS inventory_bookings (
                id INT PRIMARY KEY
                , inventory_id INT NOT NULL
                , date DATE NOT NULL
                , start_time TIME NOT NULL
                , end_time TIME NOT NULL
                , booked_by VARCHAR(50) NOT NULL
                , status ENUM('VALID', 'INVALID', 'CANCELLED') DEFAULT 'VALID'
                , comment VARCHAR(200)
                , created_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                , modified_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )"
            );

        $res = $this->query( "
            CREATE TABLE IF NOT EXISTS borrowing (
                id INT PRIMARY KEY
                , inventory_id INT NOT NULL
                , borrowed_on DATETIME NOT NULL -- borrowing date.
                , borrower VARCHAR(50) NOT NULL
                , lender VARCHAR(50) NOT NULL
                , status ENUM('VALID', 'INVALID', 'RETURNED', 'CANCELLED') DEFAULT 'VALID'
                , comment VARCHAR(200)
                , created_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                , modified_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )"
            );

        // Transport
        $res = $this->query( "CREATE TABLE IF NOT EXISTS transport (
                id INT PRIMARY KEY
                , vehicle VARCHAR(50) NOT NULL
                , pickup_point VARCHAR(50) NOT NULL
                , drop_point VARCHAR(50) NOT NULL
                , day ENUM('Sun','Mon','Tue','Wed','Thu','Fri','Sat') NOT NULL
                , trip_start_time TIME NOT NULL
                , trip_end_time TIME NOT NULL
                , url VARCHAR(1000) 
                , status ENUM('VALID','INVALID','CANCELLED') DEFAULT 'VALID'
                , score INT DEFAULT '0'
                , last_modified_on DATETIME ON UPDATE CURRENT_TIMESTAMP
                , edited_by VARCHAR(100) default 'HIPPO'
                , comment VARCHAR(200)
                , UNIQUE KEY(vehicle,day,pickup_point,drop_point,trip_start_time,status)
                )"
            );

        // Canteen
        $res = $this->query( "CREATE TABLE IF NOT EXISTS canteen_menu (
                id INT PRIMARY KEY
                , name VARCHAR(30) NOT NULL
                , description TEXT 
                , price DECIMAL(4,2) DEFAULT 0.0 
                , which_meal ENUM('BREAKFAST', 'LUNCH', 'EVENING TEA', 'DINNER', 'ALL DAY') NOT NULL
                , available_from TIME
                , available_upto TIME
                , canteen_name VARCHAR(50) NOT NULL
                , day ENUM('Sun','Mon','Tue','Wed','Thu','Fri','Sat') NOT NULL
                , days_csv VARCHAR(30) -- csv e.g. sun,mon,tue etc. (helper field)
                , modified_by VARCHAR(500) DEFAULT ''
                , modified_on DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                , status ENUM('VALID', 'INVALID', 'AVAILABLE', 'NOT AVAILABLE') DEFAULT 'VALID'
                , popularity INT DEFAULT '0'
                , UNIQUE KEY (name,canteen_name,which_meal,day)
                )"
            );

        // Location.
        $res = $this->query( "
            CREATE TABLE IF NOT EXISTS geolocation (
                id INT PRIMARY KEY AUTO_INCREMENT,
                latitude DECIMAL(10,8) NOT NULL,
                longitude DECIMAL(10,8) NOT NULL,
                altitude DECIMAL(4,3),
                accuracy DECIMAL(4,3),
                heading DECIMAL(4,3),
                speed DECIMAL(4,3),
                device_id VARCHAR(50),
                session_num INT, -- It comes from the client. Must be a random number at the time of initialization.
                crypt_id VARCHAR(50), -- one way string hashing for each IP.
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
                )"
            );

        // Clickable queries
        $res = $this->query( "
            CREATE TABLE IF NOT EXISTS queries (
                id INT PRIMARY KEY
                , external_id VARCHAR(50) DEFAULT 'NONE.-1' -- associated table.id in some other table
                , who_can_execute VARCHAR(100) NOT NULL -- which login can execute.
                , query VARCHAR(300) NOT NULL -- query to execute.
                , status ENUM( 'EXECUTED', 'INVALID', 'PENDING' ) DEFAULT 'PENDING'
                , last_modified_on DATETIME
                , edited_by VARCHAR(100) default 'HIPPO'
                )"
            );

        // API keys.
        $res = $this->query("
            CREATE TABLE IF NOT EXISTS apikeys (
                id INT PRIMARY KEY AUTO_INCREMENT
                , login VARCHAR(50) NOT NULL
                , apikey VARCHAR(40) NOT NULL
                , level INT(2) NOT NULL
                , ignore_limits TINYINT(1) NOT NULL DEFAULT '0'
                , timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()
                , UNIQUE KEY (login,apikey,level)
                ) DEFAULT CHARSET=utf8"
            );

        // Logs related to API.
        $res = $this->query("
            CREATE TABLE IF NOT EXISTS apilogs (
                id INT(11) PRIMARY KEY AUTO_INCREMENT
                , query VARCHAR(300) NOT NULL
                , timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()
                , ip VARCHAR(100) 
                , status VARCHAR(20) default 'UNKNOWN' -- success,  warning, error etc.
                ) DEFAULT CHARSET=utf8;"
            );

        return $res;
    }
}

// Construct the PDO
// And initiaze the database.
$hippoDB = new BMVPDO( "localhost" );
$hippoDB->initialize();

?>
