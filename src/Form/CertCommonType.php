<?php

namespace App\Form;

use App\Entity\Domain;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Validator\Constraints\File;
use Doctrine\ORM\EntityRepository;

class CertCommonType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $domain = $options['domain'];
        $notAfter = new \DateTime();
        $notAfter->add(\DateInterval::createFromDateString($options['duration']));
        $builder
        ->add(
            'countryName',
            CountryType::class,
            [
                'label' => 'countryName',
                'data' => 'ES',
                'required' => true,
                'attr' => [
                    'readonly' => null!=$options['subject'],
                ]
            ]
        )
        ->add(
            'stateOrProvinceName',
            TextType::class,
            [
                'label' => 'stateOrProvinceName',
                'data' => $options['subject']['stateOrProvinceName']??null,
                'required' => true,
                'attr' => [
                    'readonly' => null!=$options['subject'],
                    'autocomplete' => 'new-password'
                ]
            ]
        )
        ->add(
            'localityName',
            TextType::class,
            [
                'label' => 'localityName',
                'data' => $options['subject']['localityName']??null,
                'required' => true,
                'attr' => [
                    'readonly' => null!=$options['subject'],
                    'autocomplete' => 'new-password'
                ]
            ]
        )
        ->add(
            'organizationalUnitName',
            TextType::class,
            [
                'label' => 'organizationalUnitName',
                'data' => $options['subject']['organizationalUnitName']??null,
                'required' => false,
                'attr' => [
                    'readonly' => null!=$options['subject'],
                    'autocomplete' => 'new-password'
                ]
            ]
        )
        ->add(
            'organizationName',
            TextType::class,
            [
                'label' => 'organizationName',
                'required' => true,
                'data' => $options['subject']['organizationName']??null,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'readonly' => null!=$options['subject'],
                ]
            ]
        );
        if ($options['certtype']!='client') {
            $builder
            ->add(
                'commonName',
                TextType::class,
                [
                    'label' => 'commonName',
                    'required' => true,
                    'data' => $options['subject']['commonName']??null,
                    'attr' => [
                        'readonly' => null!=$options['subject']&&$options['certtype']=='ca',
                        'autocomplete' => 'new-password'
                    ]
                ]
            )
            ;
            if ($options['certtype']=='ca' && null==$options['subject']) {
                $builder
                ->add(
                    'customFile',
                    FileType::class,
                    [
                        'mapped' => false,
                        'required' => false,
                        'constraints' => [
                            new File(
                                [
                                    'maxSize' => '1M'
                                ]
                            )
                        ]
                    ]
                )
                ->add(
                    'plainPassword',
                    CertDownloadType::class,
                    [
                        'label' => 'Private key password'
                    ]
                )
                ;
            }
        }
        if ($options['certtype']=='client') {
            $builder
            ->add(
                'emailAddress',
                EntityType::class,
                [
                    'class' => User::class,
                    'label' => 'emailAddress',
                    'query_builder' => function (EntityRepository $er) use ($domain) {
                        $qb = $er->createQueryBuilder('u');
                        $qb
                        ->where('u.list = 0');
                        if ($domain!=0) {
                            $qb
                              ->andWhere('u.domain = :domain')
                              ->andWhere('u.certdata IS NULL')
                              ->setParameter('domain', $domain)
                            ;
                        }
                        return $qb;
                    },
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
