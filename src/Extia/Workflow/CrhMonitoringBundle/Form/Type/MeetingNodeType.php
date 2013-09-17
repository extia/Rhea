<?php

namespace Extia\Workflow\CrhMonitoringBundle\Form\Type;

use Extia\Bundle\TaskBundle\Form\Type\AbstractNodeType;

use Symfony\Component\Form\FormBuilderInterface;

/**
 * form type for meeting node
 * @see Extia/Workflow/CrhMonitoringBundle/Resources/workflows/meeting.xml
 */
class MeetingNodeType extends AbstractNodeType
{
    /**
     * {@inherit_doc}
     */
    public function getName()
    {
        return 'meeting_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('next_meeting', 'choice', array(
            'required' => true,
            'multiple' => false,
            'expanded' => false,
            'choices'  => array(
                '3'  => '3 '.$this->translator->trans('month'),
                '6'  => '6 '.$this->translator->trans('month'),
                '1'  => '1 '.$this->translator->trans('month')
            ),
            'label'    => 'crh_monitoring.meeting.next_meeting'
        ));

        $builder->add(
            $builder->create('crh_meeting_doc', 'document', array(
                    'button_label' => 'crh_meeting.form.doc_label_button',
                    'required'     => true,
                    'label'        => 'crh_monitoring.meeting.crh_meeting_doc'
                ))
                ->addModelTransformer($this->createDocumentTransformer($options))
        );
    }
}
