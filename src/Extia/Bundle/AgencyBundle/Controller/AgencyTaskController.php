<?php

namespace Extia\Bundle\AgencyBundle\Controller;

use Extia\Bundle\UserBundle\Model\MissionOrder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Extia\Bundle\UserBundle\Model\Internal;
use Extia\Bundle\TaskBundle\Model\Task;

use Extia\Bundle\UserBundle\Model\ConsultantQuery;
use Extia\Bundle\UserBundle\Model\InternalQuery;
use Extia\Bundle\TaskBundle\Model\TaskQuery;
use Extia\Bundle\UserBundle\Model\MissionOrderQuery;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AgencyTaskController extends Controller
{

    /**
     * returns team user ids
     * @return array
     */
    public function getAgencyIdsAction($internalAgencyId)
    {
        $agencyIdCollection = InternalQuery::create()
            ->setComment(sprintf('%s l:%s', __METHOD__, __LINE__))
            ->select(array('Id'))
            ->filterByAgencyId($internalAgencyId)
            ->find();
        return $agencyIdCollection;
    }

    /**
    * list agency past tasks for given user
    *
    * @param Request  $request
    * @param Internal $internal
    *
    * @return Response
    */

    //Recuperer les id de tout les internes de l'agence puis chopper toutes leurs tasks

    public function agencyLateTasksAction(Request $request, $internalAgencyId)
    {
        $taskCollection = TaskQuery::create()
            ->setComment(sprintf('%s l:%s', __METHOD__, __LINE__))
            ->joinWith('Comment', \Criteria::LEFT_JOIN)
            ->joinWithCurrentNodes()

            ->filterByAssignedTo($this->getAgencyIdsAction($internalAgencyId)->getData())
            ->filterByWorkflowTypes(array_keys($this->get('workflows')->getAllowed('read')))

            ->filterByCompletionDate(array('max' => 'now'))

            ->orderByActivationDate()
            ->findWithTargets();

        return $this->render('ExtiaAgencyBundle:Dashboard:agency_late_tasks.html.twig', array (
            'tasks' => $taskCollection
        ));
    }

    public function agencyPastTasksAction(Request $request, Internal $internal)
    {

        return $this->render('ExtiaAgencyBundle:Dashboard:agency_infos.html.twig', array(
            'internals' => $internalCollection,
            'consultants' => $consultantCollection,
            'nbInMission' => $nbInMission
        ));

    }
}