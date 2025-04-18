<?php

namespace eggnukes\scheduler;
use yii\console\Controller;
use yii\di\Instance;


/**
 * Run the scheduled commands
 */
class ScheduleController extends Controller
{
    /**
     * @var Schedule
     */
    public $schedule = 'schedule';
    /**
     * @var string Schedule file that will be used to run schedule
     */
    public $scheduleFile = '@app/config/schedule.php';

    /**
     * @var bool set to true to avoid error output
     */
    public $omitErrors = false;

    public function options($actionID)
    {
        return array_merge(parent::options($actionID),
            $actionID == 'run' ? ['scheduleFile', 'omitErrors'] : []
        );
    }


    public function init()
    {
        if (\Yii::$app->has($this->schedule)) {
            $this->schedule = Instance::ensure($this->schedule, Schedule::className());
        } else {
            $this->schedule = \Yii::createObject(Schedule::className());
        }
        parent::init();
    }


    public function actionRun()
    {
        $this->importScheduleFile();

        $events = $this->schedule->dueEvents(\Yii::$app);

        foreach ($events as $event) {
            $event->omitErrors($this->omitErrors);
            $this->stdout('[' . date('Y-m-d H:i:s') . '] Running scheduled command: '.$event->getSummaryForDisplay()."\n");
            $event->run(\Yii::$app);
        }

        if (count($events) === 0)
        {
            $this->stdout("[" . date('Y-m-d H:i:s') . "] No scheduled commands are ready to run.\n");
        }
    }

    protected function importScheduleFile()
    {
        if ($this->scheduleFile === null) {
            return;
        }

        $scheduleFile = \Yii::getAlias($this->scheduleFile);
        if (file_exists($scheduleFile) == false) {
            $this->stderr('[' . date('Y-m-d H:i:s') . '] Can not load schedule file '.$this->scheduleFile."\n");
            return;
        }

        $schedule = $this->schedule;
        call_user_func(function() use ($schedule, $scheduleFile) {
            include $scheduleFile;
        });
    }
}