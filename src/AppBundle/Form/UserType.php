<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\CallbackTransformer;

class UserType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('user')
        ->add('plainPassword', RepeatedType::class, array(
            'type' => PasswordType::class,
            'required' => false,
            'first_options' => array(
                'label' => 'Password',
            ),
            'second_options' => array(
                'label' => 'Confirm password',
            )
        ))
        ->add('domain', EntityType::class, array (
            'class' => 'AppBundle:Domain',
            'label' => 'Domain'
            )
        )
        ->add('active', CheckboxType::class, array(
            'label' => 'Active',
            'required' => false,
            )
        )
        ->add('admin', CheckboxType::class, array(
            'label' => 'Admin',
            'required' => false,
            )
        )
        ->add('sendemail', CheckboxType::class, array(
            'label' => 'Send welcome email',
            'required' => false,
            )
        )
        ;
        $builder->get('active')
             ->addModelTransformer(new CallbackTransformer(
                 function ($booleanAsString) {
                     // transform the string to boolean
                     return (bool)(int)$booleanAsString;
                 },
                 function ($stringAsBoolean) {
                     // transform the boolean to string
                     return (string)(int)$stringAsBoolean;
                 }
            )
        );
        $builder->get('admin')
             ->addModelTransformer(new CallbackTransformer(
                 function ($booleanAsString) {
                     // transform the string to boolean
                     return (bool)(int)$booleanAsString;
                 },
                 function ($stringAsBoolean) {
                     // transform the boolean to string
                     return (string)(int)$stringAsBoolean;
                 }
            )
        );
        $builder->get('sendemail')
             ->addModelTransformer(new CallbackTransformer(
                 function ($booleanAsString) {
                     // transform the string to boolean
                     return (bool)(int)$booleanAsString;
                 },
                 function ($stringAsBoolean) {
                     // transform the boolean to string
                     return (string)(int)$stringAsBoolean;
                 }
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\User'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_user';
    }


}
