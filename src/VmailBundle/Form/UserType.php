<?php

namespace VmailBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\CallbackTransformer;
use VmailBundle\Form\AliasType;
use VmailBundle\Form\AutoreplyType;
use VmailBundle\Entity\Virtual;

class UserType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $userLabel='User';
        $managePassword=true;
        if ($options['showList']) {
            $managePassword=false;
            $options['showAutoreply']=false;
            if ($options['showVirtual']) {
                $userLabel='Alias';
                $builder
                  ->add('virtuals', CollectionType::class,
                    [
                      'entry_type' => AliasType::class,
                      'by_reference' => false,
                      'allow_add' => true,
                      'allow_delete' => true
                    ]
                  )
            ;
            } else {
                $builder
                  ->add('aliases', CollectionType::class,
                    [
                      'entry_type' => AliasType::class,
                      'by_reference' => false,
                      'allow_add' => true,
                      'allow_delete' => true
                    ]
                  )
                ;
            }
        } else {
            $builder
            ->add('quota', IntegerType::class,
              [
                'label' => 'Quota',
                'required' => false,
              ]
            )
            ->add('admin', CheckboxType::class,
              [
                'label' => 'Admin',
                'required' => false,
              ]
            )
            ->get('admin')
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
            )
            ->add('sendemail', CheckboxType::class,
              [
                'label' => 'Send welcome email',
                'required' => false,
              ]
            )
            ->get('sendemail')
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
            )
            ;
        }

        if ($managePassword) {
            $builder
            ->add('plainPassword', RepeatedType::class,
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
              ]
            );
        }

        $builder
        ->add('user', TextType::class,
          [
            'label' => $userLabel
          ]
        )
        ->add('active', CheckboxType::class,
          [
            'label' => 'Active',
            'required' => false,
          ]
        )
        ->get('active')
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

        if ($options['showDomain']) {
            $builder
            ->add('domain', EntityType::class,
              [
                'class' => 'VmailBundle:Domain',
                'label' => 'Domain'
              ]
            )
            ;
        }
        if ($options['showAutoreply']) {
            $builder
            ->add('replys', CollectionType::class,
                [
                  'entry_type' => AutoreplyType::class,
                  'by_reference' => false,
                  'label' => ' '
                ]
            )
            ;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
          [
            'data_class' => 'VmailBundle\Entity\User',
            'showDomain' => false,
            'showAutoreply' => false,
            'showList' => false,
            'showVirtual' => false,
            'domain' => false,
          ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_user';
    }


}
