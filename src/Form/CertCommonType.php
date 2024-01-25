<?php

namespace App\Form;

use App\Entity\Domain;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\CallbackTransformer;

class CertCommonType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('countryName',
            CountryType::class,
            [
                'label' => 'Estado (ST)',
                'data' => 'ES'
            ]
        )
        ->add('stateOrProvinceName',
            TextType::class,
            [
                'label' => 'Provincia (P)',
                'required' => false
            ]
        )
        ->add('localityName',
            TextType::class,
            [
                'label' => 'Localidad (L)',
                'required' => false
            ]
        )
        ->add('organizationName',
            TextType::class,
            [
                'label' => 'Organización (O)',
                'required' => false
          ]
        )
        ->add('commonName',
            TextType::class,
            [
                'label' => 'Nombre común (CN)',
                'required' => false
            ]
        )
        ->add(
            'notBefore',
            DateType::class,
            [
                'widget' => 'single_text',
                'label' => 'Not before',
                'data'  => new \DateTime()
            ]
        )
        ->add(
            'notAfter',
            DateType::class,
            [
                'widget' => 'single_text',
                'label' => 'Not after',
            ]

        )
        ->add(
            'interval',
            DateIntervalType::class,
            [
                'label' => 'Interval'
            ]
        )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'data_class' => null,
            'domain' => null,
        ));
    }
}
