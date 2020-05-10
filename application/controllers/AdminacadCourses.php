<?php

trait AdminacadCourses
{
    // VIEWS.
    public function courses($data = [])
    {
        $this->load_adminacad_view('admin_acad_manages_current_courses', $data);
    }

    public function allcourses()
    {
        $this->load_adminacad_view('admin_acad_manages_courses');
    }

    public function slots()
    {
        $this->load_adminacad_view('admin_acad_manages_slots');
    }

    public function jc()
    {
        $this->load_adminacad_view('admin_acad_manages_jc');
    }

    public function jc_admins()
    {
        $this->load_adminacad_view('admin_acad_manages_jc_admins');
    }

    public function feedbackquestionnaire()
    {
        $this->load_adminacad_view('admin_acad_manages_course_questionnaire');
    }

    public function show_course_feedback(string $year = '', string $semester = '')
    {
        if (!$year) {
            $year = __get__($_POST, 'year', getCurrentYear());
        }
        if (!$semester) {
            $semester = __get__($_POST, 'semester', getCurrentSemester());
        }

        $data = ['cYear' => $year, 'cSemester' => $semester];
        $this->load_adminacad_view('admin_acad_show_course_feedback', $data);
    }

    public function goBackToReferrer($fallback)
    {
        // Send to referrer.
        if ($this->agent->is_referral()) {
            rediect($this->agent->referrer());
        }
        redirect($fallback);
    }

    // ACTIONS.
    public function all_courses_action($arg = '')
    {
        // Instructor extras has to be reformatted.
        $extraInstTxt = '';
        if (is_array(__get__($_POST, 'more_instructors', false))) {
            $extraInstTxt = implode(',', $_POST['more_instructors']);
        }

        // Append to already existibg extra instructiors.
        if (__get__($_POST, 'instructor_extras', '') && $extraInstTxt) {
            if ($extraInstTxt) {
                $_POST['instructor_extras'] .= ',' . $extraInstTxt;
            } elseif ($extraInstTxt) {
                $_POST['instructor_extras'] = $extraInstTxt;
            }
        }

        if ('delete' == $_POST['response']) {
            // We may or may not get email here. Email will be null if autocomplete was
            // used in previous page. In most cases, user is likely to use autocomplete
            // feature.
            if (strlen($_POST['id']) > 0) {
                $res = deleteFromTable('courses_metadata', 'id', $_POST);
                if ($res) {
                    echo flashMessage('Successfully deleted entry');
                } else {
                    echo printWarning('Failed to delete speaker from database');
                }
            }
        } elseif ('Add' == $_POST['response']) {
            echo printInfo('Adding a new course in current course list');
            if (strlen($_POST['id']) > 0) {
                $res = insertCourseMetadata($_POST);
                if (!$res) {
                    echo printWarning('Could not add course to list');
                } else {
                    echo flashMessage('Successfully added new course.');
                }
            } else {
                echo printWarning('Course ID can not be empty!');
            }
        } elseif ('Update' == $_POST['response']) {
            $res = updateCourseMetadata($_POST);
            if ($res) {
                echo flashMessage('Updated course : ' . $_POST['id']);
            }
        }
        redirect('adminacad/allcourses');
    }

    // Current courses.
    public function courses_action($arg = '')
    {
        $response = strtolower($_POST['response']);
        // If no venue is found, the leave it empty.
        if (!__get__($_POST, 'venue', '')) {
            $_POST['venue'] = '';
        }

        if ('do_nothing' == $response) {
            flashMessage('User said do nothing.');
            $this->load_adminacad_view('courses');

            return;
        } elseif ('delete' == $response) {
            // We may or may not generate here. Email will be null if autocomplete was
            // used in previous page. In most cases, user is likely to use autocomplete
            // feature.
            $res = deleteRunningCourse($_POST);
            flashMessage($res['msg']);
            redirect('adminacad/courses');

            return;
        }
        // Add or Update here.

        $required = ['start_date', 'end_date'];
        foreach ($required as $k) {
            if (!__get__($_POST, $k, '')) {
                printErrorSevere("Incomplete entry. $k is not found.");
                $this->load_adminacad_view('admin_acad_manages_current_courses', $_POST);

                return;
            }
        }

        $res = addOrUpdateRunningCourse($_POST, $response);
        flashMessage($res['msg']);
        $this->load_adminacad_view('admin_acad_manages_current_courses', $_POST);
    }

    public function slots_action($arg = '')
    {
        $response = strtolower($_POST['response']);
        if ('do_nothing' == $response) {
            flashMessage('User said do nothing.');
        } elseif ('delete' == $response) {
            // We may or may not get email here. Email will be null if autocomplete was
            // used in previous page. In most cases, user is likely to use autocomplete
            // feature.
            if (strlen($_POST['id']) > 0) {
                $res = deleteFromTable('slots', 'id', $_POST);
            }
            if ($res) {
                flashMessage('Successfully deleted entry.');
            } else {
                printWarning('Failed to delete slot from database.');
            }
        } else {   // update
            // Get group id of slot.
            $gid = slotGroupId($_POST['id']);
            $_POST['groupid'] = $gid;

            $res = insertOrUpdateTable('slots', 'id,groupid,day,start_time,end_time', 'day,start_time,end_time', $_POST
                );

            if ($res) {
                flashMessage('Updated/Inserted slot.');
            }
        }
        redirect('adminacad/slots');
    }

    public function addquestion()
    {
        $res = insertIntoTable('course_feedback_questions', 'id,category,subcategory,question,choices,type,status,last_modified_on', $_POST);
        if (!$res) {
            printWarning('Failed to add question to database.');
        } else {
            flashMessage('Successfully added question to database.');
        }

        // Send to referrer.
        $this->goBackToReferrer('adminacad/feedbackquestionnaire');
    }

    public function deletequestion($qid)
    {
        $res = updateTable('course_feedback_questions', 'id', 'status', ['id' => $qid, 'status' => 'INVALID']
            );
        if (!$res) {
            printWarning('Failed to invalidate question from database.');
        } else {
            flashMessage('Successfully invalidated question.');
        }

        $this->goBackToReferrer('adminacad/feedbackquestionnaire');
    }
}
