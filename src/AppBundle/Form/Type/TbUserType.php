<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class TbUserType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options){
        $builder->add('idUser', IntegerType::class, array('label' => 'Usuário'));
    }
    
    public function configureOptions(OptionsResolver $resolver){
        $resolver->setDefaults(array('data_class' => 'AppBundle\Entity\TbUser'));
    }
}
?>