<?php

namespace App\Form;

use App\Entity\Autoreply;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
//use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\CallbackTransformer;

class AutoreplyType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add(
            'message',
            TextareaType::class,
            [
                'label' => 'Body message',
                'required' => true,
                'attr' => [
                    'cols' => 100,
                    'rows' => 3
                ]
            ]
        )
        ->add(
            'startdate',
            null,
            [
                'widget' => 'single_text',
                'label' => 'Start date',
            ]
        )
        ->add(
            'enddate',
            null,
            [
                'widget' => 'single_text',
                'label' => 'End date',
            ]
        )
        ->add(
            'active',
            CheckboxType::class,
            [
                'label' => 'Autoreply active',
                'required' => false,
            ]
        )
        ;
        /* $builder
        ->get('active')
        ->addModelTransformer(
            new CallbackTransformer(
                function ($booleanAsString) {
                    // transform the string to boolean
                    return (bool)(int)$booleanAsString;
                },
                function ($stringAsBoolean) {
                    // transform the boolean to string
                    return (string)(int)$stringAsBoolean;
                }
            )
        ); */
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Autoreply::class,
        ));
    }

    /**
     * {@inheritdoc}
     */
    /*public function getBlockPrefix()
    {
        return 'vmailbundle_autoreply';
    } */
}
