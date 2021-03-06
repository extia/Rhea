<?php

namespace Extia\Bundle\TaskBundle\Form\Handler;

use Extia\Bundle\TaskBundle\Domain\TaskDomain;
use Extia\Bundle\TaskBundle\Model\Task;
use Extia\Bundle\TaskBundle\Tools\TemporalTools;

use Extia\Bundle\UserBundle\Model\ConsultantQuery;
use Extia\Bundle\MissionBundle\Model\MissionQuery;
use Extia\Bundle\NotificationBundle\Notification\NotifierInterface;

use EasyTask\Bundle\WorkflowBundle\Workflow\Aggregator;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
/**
 * Base class for node handlers
 * @see Extia/Bundles/TaskBundle/Resources/config/services.xml
 */
abstract class AbstractNodeHandler
{
    protected $workfows;
    protected $notifier;
    protected $taskDomain;
    protected $temporalTools;

    /**
     * setup dependencies
     * @param Aggregator        $workflows
     * @param TaskDomain        $taskDomain
     * @param NotifierInterface $notifier
     */
    public function setup(Aggregator $workflows, TaskDomain $taskDomain, NotifierInterface $notifier, TemporalTools $temporalTools)
    {
        $this->workflows     = $workflows;
        $this->taskDomain    = $taskDomain;
        $this->notifier      = $notifier;
        $this->temporalTools = $temporalTools;
    }

    /**
     * form handling method
     * @param  Form     $form
     * @param  Request  $request
     * @param  Task     $task
     * @return Response | null
     */
    public function handle(Form $form, Request $request, Task $task)
    {
        $form->submit($request);
        if (!$form->isValid()) {
            return false;
        }

        // updates task with incomming data
        $return = $this->resolve(
            $form->getData(), $task
        );

        // fire notification if successfull
        if ($return) {
            $this->fireTaskNotification($task);
        }

        return $return;
    }

    /**
     * triggers node notification throught node notifyAction on call
     *
     * @param  Task                     $task
     * @throws InvalidArgumentException if task doesnt supports node notification
     *
     * @see NotifierInterface
     */
    public function fireTaskNotification(Task $task)
    {
        $nodeType = $task->getNode()->getType();

        if (!$nodeType->supportsAction('notify')) {
            throw new \InvalidArgumentException(sprintf('%s@%s task node doesnt supports "notify" action.',
                $task->getNode()->getName(),
                $task->getNode()->getWorkflow()->getType()
            ));
        }

        $this->notifier->add(
            'success',
            $nodeType->getAction('notify'),
            array('Id' => $task->getId()),
            'controller'
        );
    }

    /**
     * notify task given node name as next node
     * @param  string        $nodeName
     * @param  Task          $task
     * @return Response|null
     */
    public function notifyNext($nodeName, Task $task, array $parameters = array(), \Pdo $pdo = null)
    {
        $workflow = $task->getNode()->getWorkflow();

        return $this->workflows
            ->getNode($workflow, $nodeName)
            ->notify($workflow, $parameters, $pdo);
    }

    /**
     * node handling method
     * @param  array    $data
     * @param  Task     $task
     * @return Response | null
     */
    abstract public function resolve(array $data, Task $task, \Pdo $pdo = null);

    /**
     * updates task linked workflow if workflow data given
     * @param array $data
     * @param Task  $task
     * @param Pdo   $con  optionnal db connection
     */
    protected function updateWorkflow(array $data, Task $task, \Pdo $con = null)
    {
        if (empty($data['workflow'])) {
            return;
        }

        $workflow = $task->getNode($con)->getWorkflow($con);

        if (isset($data['workflow']['name'])) {
            $workflow->setName($data['workflow']['name']);
        }
        if (isset($data['workflow']['description'])) {
            $workflow->setDescription($data['workflow']['description']);
        }

        $workflow->save($con);
    }

    /**
     * load consultant from it's id
     * @param  int        $pk
     * @return Consultant
     */
    protected function loadConsultant($pk, \Pdo $pdo = null)
    {
        $consultant = ConsultantQuery::create()
            ->setComment(sprintf('%s l:%s', __METHOD__, __LINE__))
            ->findPk($pk, $pdo)
        ;

        if (empty($consultant)) {
            throw new \RuntimeException('Selected consultant doesnt exists for given id : '.$pk);
        }

        return $consultant;
    }

    /**
     * load mission from it's id
     * @param  int          $pk
     * @return Mission
     */
    public function loadMission($pk, \Pdo $pdo = null)
    {
        $mission = MissionQuery::create()
            ->setComment(sprintf('%s l:%s', __METHOD__, __LINE__))
            ->findPk($pk, $pdo)
        ;

        if (empty($mission)) {
            throw new \RuntimeException('Selected mission doesnt exists for given id : '.$pk);
        }

        return $mission;
    }

}
