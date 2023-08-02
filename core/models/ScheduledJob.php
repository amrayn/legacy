<?php

includeOnce("core/models/BaseModel.php");

abstract class ScheduledJobStates
{
    const Waiting = 0;
    const Running = 1;
    const Failed = 2;
    const FatalError = 3;
    const TimedOut = 3;
}

class ScheduledJob extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(), array(
            "name",
            "schedule",
            "state",
            "stateChanged"));
    }



    public function isWaiting()
    {
        return $this->state == ScheduledJobStates::Waiting;
    }

    public function isRunning()
    {
        return $this->state == ScheduledJobStates::Running;
    }

    public function isFailed()
    {
        return $this->state == ScheduledJobStates::Failed;
    }

    public function isDead()
    {
        return $this->state == ScheduledJobStates::FatalError;
    }
}
