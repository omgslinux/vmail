<?php

namespace App\Form;

use App\Dto\CertDto;
use App\Entity\Domain;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\CallbackTransformer;

class CertType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        //$notAfter = new \DateTime();
        //$notAfter->add(\DateInterval::createFromDateString($options['duration']));
        //dump($options);
        $builder
        ->add(
            'common',
            CertCommonType::class,
            [
                'label' => false,
                'dto' => $options['dto'],
                //'certtype' => $options['dto']->getCerttype(),
            ]
        )
        ->add(
            'interval',
            CertIntervalType::class,
            [
                'dto' => $options['dto'],
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
            'data_class' => CertDto::class,
            'dto' => null,
            'validation_groups' => function (FormInterface $form): array {
                $data = $form->getData();
                $common = $data->getCommon();
                $groups = ['Default'];
                if ($data->getCerttype()=='ca') {
                    if (null != $common->getCustomFile()) {
                        $groups [] = 'with_file';
                        return $groups;
                    }

                    if (null==$common->getCommonName()) {
                        $groups [] = 'group_cn';
                    } elseif (null==$common->getCountryName()) {
                        $groups[]='group_co';
                    } elseif (null==$common->getStateOrProvinceName()) {
                        $groups[]='group_st';
                    } elseif (null==$common->getLocalityName()) {
                        $groups[]='group_loc';
                    } elseif (null==$common->getOrganizationName()) {
                        $groups[]='group_org';
                    } elseif (null==$common->getOrganizationalUnitName()) {
                        $groups[]='group_orgun';
                    } else {
                        $groups[]='password';
                    }
                } elseif ($data->getCerttype()=='server') {
                    $groups[]= 'group_cn';
                }

                return $groups;
            },
            'download' => false,
        ));
    }
}
