<?php

namespace Extia\Workflow\CrhMonitoringBundle\Form\Type;

use Extia\Bundle\ExtraWorkflowBundle\Form\Type\AbstractNodeType;

use Symfony\Component\Form\FormBuilderInterface;

/**
 * form type for appointement node
 * @see Extia/Workflow/CrhMonitoringBundle/Resources/workflows/appointement.xml
 */
class AppointementNodeType extends AbstractNodeType
{
    /**
     * {@inherit_doc}
     */
    public function getName()
    {
        return 'appointement_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('meeting_date', 'datetime', array(
            'required'    => true,
            'date_widget' => 'text',
            'time_widget' => 'text',
            'input'       => 'timestamp'
        ));
    }
}