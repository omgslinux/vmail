<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Domain;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\CallbackTransformer;
use App\Form\AliasType;
use App\Form\AutoreplyType;

class UserType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $userLabel='User';
        $fullNameLabel='Full Name';
        $domain=$options['domain'];
        $managePassword=true;
        if ($options['showAlias'] || $options['showList']) {
            $managePassword=false;
            $options['showAutoreply']=false;
            $userLabel='Alias';
            $fullNameLabel='Description';
            if ($options['showAlias']) {
                $builder
                ->add(
                    'aliasnames',
                    CollectionType::class,
                    [
                      'entry_type' => AliasType::class,
                      'by_reference' => false,
                      'allow_add' => true,
                      'allow_delete' => true,
                      'entry_options' =>
                      [
                        'domain' => $domain
                      ]
                    ]
                )
                ;
            }
        } else {
            $builder
            ->add(
                'quota',
                IntegerType::class,
                [
                  'label' => 'Quota',
                  'required' => false,
                ]
            )
            ->add(
                'admin',
                CheckboxType::class,
                [
                  'label' => 'Admin',
                  'required' => false,
                ]
            )
            ->add(
                'sendemail',
                CheckboxType::class,
                [
                  'label' => 'Send welcome email',
                  'required' => false,
                ]
            )
            ;

            $builder
            ->get('admin')
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
            )
            ;

            $builder
            ->get('sendemail')
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
            )
            ;
        }
        $builder
        ->add(
            'name',
            TextType::class,
            [
                'label' => $userLabel,
                'attr' =>
                [
                  'placeholder' => 'Enter username'
                ]
            ]
        )
        ->add(
            'fullname',
            TextType::class,
            [
                'label' => $fullNameLabel,
                'attr' =>
                [
                  'placeholder' => 'Enter full name'
                ]
            ]
        )
        ->add(
            'active',
            CheckboxType::class,
            [
                'label' => 'Active',
                'required' => false,
            ]
        )
        ;
        $builder
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
        );

        if ($managePassword===true) {
            $builder
            ->add(
                'plainPassword',
                RepeatedType::class,
                [
                    'type' => PasswordType::class,
                    'required' => false,
                    'first_options' =>
                    [
                        'label' => 'Password',
                    ],
                        'second_options' =>
                    [
                        'label' => 'Confirm password',
                    ],
                    'attr' => [
                        'autocomplete' => 'off'
                    ]
                ]
            )
            ;
        }

        if ($options['showDomain']) {
            $builder
            ->add(
                'domain',
                EntityType::class,
                [
                    'class' => Domain::class,
                    'label' => 'Domain'
                ]
            )
            ;
        }
        if ($options['showAutoreply']) {
            $builder
            ->add(
                'reply',
                AutoreplyType::class,
                [
                    //'entry_type' => AutoreplyType::class,
                    'by_reference' => true,
                    'label' => false
                ]
            )
            ;
        }
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => User::class,
                'showDomain' => false,
                'showAutoreply' => false,
                'showList' => false,
                'showAlias' => false,
                'domain' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'vmailbundle_user';
    }
}
