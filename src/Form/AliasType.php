<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\User;
use App\Entity\Alias;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Doctrine\ORM\EntityRepository;

class AliasType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $domain=$options['domainId'];
        $builder
        ->add(
            'addressname',
            EntityType::class,
            [
                'class' => User::class,
                'label' => 'Alias address',
                'query_builder' => function (EntityRepository $er) use ($domain) {
                    $qb = $er->createQueryBuilder('u');
                    $qb
                    ->where('u.list = 0');
                    if ($domain!=0) {
                        $qb
                          ->andWhere('u.domain = :domain')
                          ->setParameter('domain', $domain)
                        ;
                    }
                    return $qb;
                },
            ]
        )
        ->add(
            'active',
            CheckboxType::class,
            [
                'required' => false,
                'label' => false
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
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Alias::class,
            'domainId' => 0,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'vmailbundle_aliasname';
    }
}
