<?php

namespace App\Form;

use App\Entity\Domain;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class CertIntervalType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $notAfter = new \DateTime();
        $notAfter->add(\DateInterval::createFromDateString($options['duration']));
        $builder
        ->add(
            'notBefore',
            DateType::class,
            [
                'widget' => 'single_text',
                'label' => 'NotBefore',
                'data'  => new \DateTime()
            ]
        )
        ->add(
            'notAfter',
            DateType::class,
            [
                'widget' => 'single_text',
                'label' => 'NotAfter',
                'data'  => $notAfter
          ]

        )
        ->add(
            'interval',
            DateIntervalType::class,
            [
                'label' => 'Interval',
                'data' => \DateInterval::createFromDateString($options['duration']),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'data_class' => null,
            'duration' => '1 years',
        ));
    }
}
