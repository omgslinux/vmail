<?php

namespace App\Form;

use App\Dto\CertDto;
use App\Dto\CertIntervalDto;
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
        //dump($options);
        /*if (null==$options['interval']) {
            $notBefore = new \DateTime();
            $notAfter = new \DateTime();
            $notAfter->add(\DateInterval::createFromDateString($options['duration']));
            //dump($options);
        } else {
            $notBefore = $options['interval']['NotBefore'];
            $notAfter = $options['interval']['NotAfter'];
        }*/
        $builder
        ->add(
            'notBefore',
            DateType::class,
            [
                'label' => 'NotBefore',
                'disabled' => !$options['dto']->isNew(),
                'widget' => 'single_text',
                //'data' => \DateInterval::createFromDateString($options['interval']['NotBefore']),
                //'data'  => new \DateTime()
                //'data'  => $options['interval']['NotBefore']
                //'data' => (null!=$options['interval']?$options['interval']['NotBefore']:null),
                //'data' => $notBefore
            ]
        )
        ->add(
            'notAfter',
            DateType::class,
            [
                'label' => 'NotAfter',
                'widget' => 'single_text',
                'disabled' => !$options['dto']->isNew(),
                //'disabled' => null!=$options['interval'],
                //'data' => (null!=$options['interval']?$options['interval']['NotAfter']:null),
                //'data'  => $notAfter
            ]
        );
        if ($options['dto']->isNew()) {
            $builder
            ->add(
                'interval',
                DateIntervalType::class,
                [
                    'label' => 'Interval',
                    //'disabled' => !$options['dto']->isNew(),
                    //'data' => \DateInterval::createFromDateString($options['duration'])??null,
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
            'data_class' => CertIntervalDto::class,
            'dto' => null,
            //'duration' => '1 years',
            //'interval' => null,
        ));
    }
}
