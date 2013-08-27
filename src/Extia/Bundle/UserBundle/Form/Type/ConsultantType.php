<?php
namespace Extia\Bundle\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Form type for consultant model
 * @see Extia/Bundles/UserBundle/Resources/config/admin.xml
 */
class ConsultantType extends AdminType
{
    /**
     * @see AbstracType::getName()
     */
    public function getName()
    {
        return 'consultant_form';
    }

    /**
     * @see AbstractType::setDefaultOptions()
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        return $resolver->setDefaults(array());
    }

    /**
     * @see AbstractType::buildForm()
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

    }

}
