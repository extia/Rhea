<?php

namespace Extia\Bundle\UserBundle\Form\Handler;

use Extia\Bundle\UserBundle\Model\InternalQuery;
use Extia\Bundle\UserBundle\Model\Resignation;
use Extia\Bundle\UserBundle\Model\ConsultantQuery;

use Extia\Bundle\TaskBundle\Model\TaskQuery;
use Extia\Bundle\MissionBundle\Model\MissionQuery;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

/**
 * Form handler for internals creation / editing
 * @see Extia/Bundles/UserBundle/Resources/config/admin.xml
 */
class InternalHandler extends AdminHandler
{
    /**
     * handle method
     * @param  Request $request
     * @param  Form    $form
     * @return bool
     */
    public function handle(Form $form, Request $request)
    {
        $form->submit($request);

        $pdo = \Propel::getConnection('default');
        $pdo->beginTransaction();

        $parentId = $form->get('parent')->getData();
        $parent   = InternalQuery::create()
            ->setComment(sprintf('%s l:%s', __METHOD__, __LINE__))
            ->findPk($parentId, $pdo);

        if (!$form->isValid() || empty($parent)) {
            $this->catchEmailExistsError($form);
            $this->notifier->add('warning', 'internal.admin.notifications.invalid_form');

            return false;
        }

        $internal = $form->getData();

        try {
            // image
            $this->handleInternalImage($form, $internal);

            // password updating
            $this->handleInternalPassword($form, $internal);

            // trigram to upper
            $internal->setTrigram(strtoupper($internal->getTrigram()));

            // saving with NestedSet
            if ($internal->isNew()) {
                $internal->insertAsLastChildOf($parent, $pdo);
            } elseif (!$internal->isRoot()) {
                $internal->moveToLastChildOf($parent, $pdo);
            }

            if (!$internal->isNew()) {
                $resignation = $form->get('resignation')->getData();
                if (!empty($resignation['resign_internal'])) {

                    // creates a new resignation
                    $resign = new Resignation();
                    $resign->setLeaveAt($resignation['leave_at']);
                    $resign->setCode($resignation['resignation_code']);
                    $resign->setComment($resignation['reason']);
                    $resign->setResignedById($this->securityContext->getToken()->getUser()->getId());

                    $internal->setResignation($resign);

                    // re-assign all
                    $internalId = $resignation['assign_all_to'];

                    // tasks
                    $nbTasks = TaskQuery::create()
                        ->setComment(sprintf('%s l:%s', __METHOD__, __LINE__))
                        ->filterByAssignedTo($internal->getId())
                        ->update(array('AssignedTo' => $internalId), $pdo);

                    // missions
                    $nbMissions = MissionQuery::create()
                        ->setComment(sprintf('%s l:%s', __METHOD__, __LINE__))
                        ->filterByManagerId($internal->getId())
                        ->update(array('ManagerId' => $internalId), $pdo);

                    // consultants as crh
                    $nbCltCrh = ConsultantQuery::create()
                        ->setComment(sprintf('%s l:%s', __METHOD__, __LINE__))
                        ->filterByCrhId($internal->getId())
                        ->update(array('CrhId' => $internalId), $pdo);

                    // consultants as mng
                    $nbCltMng = ConsultantQuery::create()
                        ->setComment(sprintf('%s l:%s', __METHOD__, __LINE__))
                        ->filterByManagerId($internal->getId())
                        ->update(array('ManagerId' => $internalId), $pdo);

                    // assign user team to parent
                    foreach ($internal->getChildren($pdo) as $child) {
                        $child->moveToLastChildOf($internal->getParent($pdo));
                        $child->save($pdo);
                    }
                }
            }

            $internal->save($pdo);

            // reinjects credentials into person (cascade save doesnt work with inheritance)
            $internal->getPerson()->setPersonCredentials(
                $internal->getPersonCredentials()
            );

            $internal->save($pdo);

            $pdo->commit();

            // success message
            $this->notifier->add(
                'success', 'internal.admin.notifications.save_success',
                array ('%internal_name%' => $internal->getLongName())
            );

            return true;

        } catch (\Exception $e) {
            $pdo->rollback();

            if ($this->debug) {
                throw $e;
            }

            $this->logger->err($e->getMessage());
            $this->notifier->add(
                'error', 'internal.admin.notifications.error'
            );

            return false;
        }
    }
}
