<?php
global $CFG;
require_once $CFG->dirroot . '/blocks/proctoru/lib.php';
require_once $CFG->dirroot . '/blocks/proctoru/Cronlib.php';
require_once $CFG->dirroot . '/blocks/proctoru/tests/conf/ConfigProctorU.php';
require_once $CFG->dirroot . '/blocks/proctoru/tests/abstract_testcase.php';

class ProctorUCronProcessor_testcase extends abstract_testcase{

    public function test_objPartitionUsersWithoutStatus(){
        $numTeachers = 10;
        $numStudents = 20;
        
        $students = $this->addNUsersToDatabase($numStudents);
        $teachers = $this->addNUsersToDatabase($numTeachers);
        $course   = $this->getDataGenerator()->create_course();
        
        foreach($students as $s) {
            $this->enrolUser($s, $course, $this->teacherRoleId);
        }
        
        foreach($teachers as $t) {
            $this->enrolUser($s, $course, $this->studentRoleId);
        }
        
        list($unreg, $exempt) = $this->cron->objPartitionUsersWithoutStatus();
        $this->assertEquals($numStudents +1 , count($unreg)); //+1 for admin
        $this->assertEquals($numTeachers, count($exempt));
    }
    
    public function test_blnSetUnregisteredForUsersWithoutStatus(){
        
        $this->buildDataset(false,true);
        
        // +4 for the enrolTestUsers
        $this->assertEquals(70+4,count(ProctorU::objGetAllUsersWithProctorStatus()));
        
        // +1 for admin
        $this->assertEquals(31,count(ProctorU::objGetAllUsersWithoutProctorStatus()));
        
        $unit = $this->cron->intSetUnregisteredForUsersWithoutStatus();
        $this->assertEquals(31, $unit);
    }
    
    public function test_constProcessUser(){
        $this->enrolTestUsers();
//        $this->addNUsersToDatabse(20, array('suspended'=>1));
//        $this->addNUsersToDatabse(20, array('deleted'=>1));
        
        // not in prod service
        $this->setClientMode($this->localDataStore, 'test');
        $this->setClientMode($this->puClient, 'test');
        
        $userWithoutOnlineSAMProfile = $this->users['userUnregistered'];
        $this->assertEquals(ProctorU::SAM_HAS_PROFILE_ERROR, $this->cron->constProcessUser($userWithoutOnlineSAMProfile));
        
        $regUserWithSamAndPuRegInTest = $this->users['userRegistered'];
        $this->assertEquals(ProctorU::REGISTERED, $this->cron->constProcessUser($regUserWithSamAndPuRegInTest));
        
        //now prod
        $this->setClientMode($this->puClient, 'prod');
        $this->setClientMode($this->localDataStore, 'prod');
        
        $verifiedUser = $this->users['userVerified'];
        $this->assertEquals(ProctorU::VERIFIED, $this->cron->constProcessUser($verifiedUser));
        
//        $this->assertEquals(ProctorU::REGISTERED, $this->cron->constProcessUser($userWithoutOnlineSAMProfile));
    }
}
?>
