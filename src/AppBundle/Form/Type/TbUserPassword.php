<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

    class TbActivityLogin extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options){
        $builder->add('email', IntegerType::class, array('label' => 'Digite o email'));
    }
    
    public function configureOptions(OptionsResolver $resolver){
        $resolver->setDefaults(array('data_class' => 'AppBundle\Entity\TbUser'));
    }
}
?>