CREATE DATABASE IF NOT EXISTS hippo;
USE hippo;

-- DROP TABLE IF EXISTS bookmyvenue_requests;
-- DROP TABLE IF EXISTS events;
-- DROP TABLE IF EXISTS venues;
DROP TABLE IF EXISTS annual_work_seminars;
-- DROP TABLE IF EXISTS aws_requests;
DROP TABLE IF EXISTS upcoming_aws;
-- DROP TABLE IF EXISTS supervisors;
-- DROP TABLE IF EXISTS labs;
-- DROP TABLE IF EXISTS logins;
-- DROP TABLE IF EXISTS faculty;


CREATE TABLE IF NOT EXISTS logins (
    id VARCHAR( 200 ) 
    , login VARCHAR(100) 
    , email VARCHAR(200)
    , alternative_email VARCHAR(200)
    , first_name VARCHAR(200)
    , last_name VARCHAR(100)
    , roles SET( 
        'USER', 'ADMIN', 'JOURNALCLUB_ADMIN', 'AWS_ADMIN', 'BOOKMYVENUE_ADMIN'
    ) DEFAULT 'USER'
    , last_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    , created_on DATETIME 
    , joined_on DATE
    , eligible_for_aws ENUM ('YES', 'NO' ) DEFAULT 'NO'
    , valid_until DATE
    , status SET( "ACTIVE", "INACTIVE", "TEMPORARLY_INACTIVE", "EXPIRED" ) DEFAULT "ACTIVE" 
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
    , PRIMARY KEY (login)
);

CREATE TABLE IF NOT EXISTS bookmyvenue_requests (
    gid INT NOT NULL
    , rid INT NOT NULL
    , user VARCHAR(50) NOT NULL
    , title VARCHAR(100) NOT NULL
    , description TEXT 
    , venue VARCHAR(80) NOT NULL
    , date DATE NOT NULL
    , start_time TIME NOT NULL
    , end_time TIME NOT NULL
    , status ENUM ( 'PENDING', 'APPROVED', 'REJECTED', 'CANCELLED' ) DEFAULT 'PENDING'
    , is_public_event ENUM( "YES", "NO" ) DEFAULT "NO"
    , url VARCHAR( 1000 )
    , modified_by VARCHAR(50) -- Who modified the request last time.
    , timestamp TIMESTAMP  DEFAULT CURRENT_TIMESTAMP 
    , PRIMARY KEY( gid, rid )
    );

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
    , PRIMARY KEY (id)
    );
    
    
DROP TABLE IF EXISTS events;
CREATE TABLE IF NOT EXISTS events (
    -- Sub even will be parent.children format.
    gid INT NOT NULL
    , eid INT NOT NULL
    -- If yes, this entry will be put on google calendar.
    , is_public_event ENUM( 'YES', 'NO' ) DEFAULT 'NO' 
    , class ENUM( 'LABMEET', 'LECTURE', 'MEETING'
        , 'SEMINAR', 'TALK', 'SCHOOL'
        , 'CONFERENCE', 'CULTURAL', 'AWS'
        , 'SPORTS'
        , 'UNKNOWN'
        ) DEFAULT 'UNKNOWN' 
    , title VARCHAR(200) NOT NULL
    , description TEXT
    , date DATE NOT NULL
    , venue VARCHAR(80) NOT NULL
    , user VARCHAR( 50 ) NOT NULL
    , start_time TIME NOT NULL
    , end_time TIME NOT NULL
    , status ENUM( 'VALID', 'INVALID', 'CANCELLED' ) DEFAULT 'VALID' 
    , calendar_id VARCHAR(500)
    , calendar_event_id VARCHAR(500)
    , url VARCHAR(1000)
    , PRIMARY KEY( gid, eid )
    , FOREIGN KEY (venue) REFERENCES venues(id)
    );
    

--  Create  a table supervisors. They are from outside.
create TABLE IF NOT EXISTS supervisors (
    email VARCHAR(200) PRIMARY KEY NOT NULL
    , first_name VARCHAR( 200 ) NOT NULL
    , middle_name VARCHAR(200)
    , last_name VARCHAR( 200 ) 
    , affiliation VARCHAR( 1000 ) NOT NULL
    , url VARCHAR(300)
    );

--  Create  a table supervisors. They are from outside.
create TABLE IF NOT EXISTS faculty (
    email VARCHAR(200) PRIMARY KEY NOT NULL
    , first_name VARCHAR( 200 ) NOT NULL
    , middle_name VARCHAR(200)
    , last_name VARCHAR( 200 ) 
    , status ENUM ( 'ACTIVE', 'INACTIVE', 'INVALID' ) DEFAULT 'ACTIVE'
    , affiliation VARCHAR( 1000 ) NOT NULL DEFAULT 'NCBS Bangalore'
    , created_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP 
    , modified_on DATETIME NOT NULL
    , url VARCHAR(300)
    );

-- These are finally approved AWS. 
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
    , abstract TEXT
    , FOREIGN KEY (speaker) REFERENCES logins(login)
    , UNIQUE KEY (speaker, date)
    , FOREIGN KEY (supervisor_1) REFERENCES faculty(email) 
    );

-- This table holds all the upcoming AWSs. 
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
    , abstract TEXT
    , status ENUM( 'VALID', 'INVALID' ) DEFAULT 'VALID'
    , comment TEXT 
    , FOREIGN KEY (speaker) REFERENCES logins(login)
    , UNIQUE (speaker, date)
    );

-- This table holds all edit and requests.
create TABLE IF NOT EXISTS aws_requests (
    id INT AUTO_INCREMENT PRIMARY KEY
    , speaker VARCHAR(200) NOT NULL -- user
    , date DATE -- final date
    , time TIME DEFAULT '16:00'
    , supervisor_1 VARCHAR( 200 ) -- first superviser must be from NCBS
    , supervisor_2 VARCHAR( 200 ) -- superviser 2, optional
    , tcm_member_1 VARCHAR( 200 ) -- Can be null at the time of inserting a query.
    , tcm_member_2 VARCHAR( 200 ) -- optional 
    , tcm_member_3 VARCHAR( 200 ) -- optional 
    , tcm_member_4 VARCHAR( 200 ) -- optional
    , scheduled_on DATE 
    , title VARCHAR( 1000 )
    , abstract TEXT
    , status ENUM( 'PENDING', 'APPROVED', 'REJECTED', 'INVALID', 'CANCELLED' ) DEFAULT 'PENDING'
    , modidfied_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

-- This table to hold other stuff about users.
create TABLE IF NOT EXISTS logins_metadata (
    login VARCHAR(200) PRIMARY KEY
    , user_image blob NOT NULL
    , other_image blob
    , FOREIGN KEY (login) REFERENCES logins( login )
    );
