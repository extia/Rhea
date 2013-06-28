<?php

namespace EasyTask\Bundle\WorkflowBundle\Workflow;

use EasyTask\Bundle\WorkflowBundle\Model\Workflow\Workflow;
use EasyTask\Bundle\WorkflowBundle\Event\WorkflowEvent;
use EasyTask\Bundle\WorkflowBundle\Event\WorkflowEvents;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * workflow aggregator
 * @see WorkflowBundle/Resources/config/workflow.xml
 */
class Aggregator extends ParameterBag
{
    protected $eventDispatcher;

    /**
     * construct
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * method called by DI, dynamically from compiler pass
     * @see EasyTask\Bundle\WorkflowBundle\DependencyInjection\Compiler\WorkflowAggregatorCompilerPass
     */
    public function addWorkflow($wfName, TypeWorkflowInterface $workflowType)
    {
        return $this->set($wfName, $workflowType);
    }

    /**
     * return required node for given workflow
     *
     * @param  Workflow|string          $workflow
     * @param  string                   $nodeName
     * @return WorkflowNode
     * @throws RuntimeException         if given workflow type is unknown
     * @throws InvalidArgumentException if workflow does not support given node name
     */
    public function getNode($workflow, $nodeName)
    {
        $wfTypeName = ($workflow instanceof Workflow) ? $workflow->getType(): $wfTypeName;
        $workflowType = $this->get($wfTypeName);
        if (empty($workflowType)) {
            throw new \RuntimeException(sprintf('Given workflow has an unsupported type : "%s". Check your configuration.',
                $wfTypeName
            ));
        }

        $nodeType = $workflowType->getNode($nodeName);
        if (empty($nodeType)) {
            throw new \RuntimeException(sprintf('Unknow given node for workflow "%s" : "%s". Check your configuration.',
                $workflow->getType(),
                $nodeName
            ));
        }

        return $nodeType;
    }

    /**
     * handle a workflow creation form
     * @param  FormInterface $form
     * @param  Request       $request
     * @return Response|null
     */
    public function handle(FormInterface $form, Request $request)
    {
        $wf    = $form->getData();
        $isNew = $wf->isNew();

        $connection = \Propel::getConnection('default');
        $connection->beginTransaction();

        try {
            $wf->save($connection);

            $workflowEvent = new WorkflowEvent($wf, $request, $connection);

            // edit case : nothing more to do
            if (!$isNew) {
                $this->eventDispatcher->dispatch(WorkflowEvents::WF_EDIT, $workflowEvent);

                return;
            }

            $this->eventDispatcher->dispatch(WorkflowEvents::WF_CREATE, $workflowEvent);

            // boot workflow throught type service
            $return = $this->get($wf->getType())->boot($wf, $request, $connection);

            $connection->commit();

            return $return;

        } catch (\Exception $e) {
            $connection->rollback();
            throw $e;
        }
    }
}
