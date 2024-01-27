<?php

namespace App\Form;

use App\Entity\Domain;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\CallbackTransformer;

class CertCommonType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $notAfter = new \DateTime();
        $notAfter->add(\DateInterval::createFromDateString($options['duration']));
        $builder
        ->add('countryName',
            CountryType::class,
            [
                'label' => 'countryName',
                'data' => 'ES'
            ]
        )
        ->add('stateOrProvinceName',
            TextType::class,
            [
                'label' => 'stateOrProvinceName',
                'data' => $options['subject']['stateOrProvinceName']??null,
                'required' => false,
                'attr' => [
                    'autocomplete' => 'new-password'
                ]
            ]
        )
        ->add('localityName',
            TextType::class,
            [
                'label' => 'localityName',
                'data' => $options['subject']['localityName']??null,
                'required' => false,
                'attr' => [
                    'autocomplete' => 'new-password'
                ]
            ]
        )
        ->add('organizationalUnitName',
            TextType::class,
            [
                'label' => 'organizationalUnitName',
                'data' => $options['subject']['organizationalUnitName']??null,
                'required' => false,
                'attr' => [
                    'autocomplete' => 'new-password'
                ]
          ]
        )
        ->add('organizationName',
            TextType::class,
            [
                'label' => 'organizationName',
                'required' => false,
                'data' => $options['subject']['organizationName']??null,
                'attr' => [
                    'autocomplete' => 'new-password'
                ]
          ]
        )
        ->add('commonName',
            TextType::class,
            [
                'label' => 'commonName',
                'required' => true,
                'attr' => [
                    'autocomplete' => 'new-password'
                ]
            ]
        )
        ;
        if (null==$options['certtype']) {
          $builder->add(
            'emailAddress',
            TextType::class,
            [
                'label' => 'emailAddress',
                'required' => true,
                'attr' => [
                    'autocomplete' => 'new-password'
                ]
            ]
          );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'data_class' => null,
            'domain' => null,
            'duration' => '1 years',
            'certtype' => null,
            'subject' => null,
        ));
    }
}
