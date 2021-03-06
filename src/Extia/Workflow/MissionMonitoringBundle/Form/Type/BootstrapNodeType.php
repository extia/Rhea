<?php

namespace Extia\Workflow\MissionMonitoringBundle\Form\Type;

use Extia\Bundle\TaskBundle\Form\Type\AbstractNodeType;

use Symfony\Component\Form\FormBuilderInterface;

/**
 * form type for bootstrap node
 * @see Extia/Workflow/MissionMonitoringBundle/Resources/workflows/bootstrap.xml
 */
class BootstrapNodeType extends AbstractNodeType
{
    /**
     * {@inherit_doc}
     */
    public function getName()
    {
        return 'mission_bootstrap_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('workflow', 'workflow_data', array(
            'required'     => true
        ));

        $builder->add('user_target_id', 'choice', array(
            'required' => true,
            'multiple' => false,
            'expanded' => false,
            'choices'  => $this->getConsultantsChoices(),
            'label'    => 'mission_monitoring.bootstrap.user_target_id'
        ));

        $builder->add('next_date', 'date', array(
            'required' => true,
            'widget'   => 'text',
            'input'    => 'timestamp',
            'format'   => 'dd/MM/yyyy',
            'label'    => 'mission_monitoring.bootstrap.next_date'
        ));
    }
}
