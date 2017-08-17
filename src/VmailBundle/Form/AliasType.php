<?php

namespace VmailBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use VmailBundle\Entity\User;
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
        $domain=(!empty($options['domain'])?$options['domain']:false);
        $builder
        ->add('aliasname', EntityType::class,
          [
            'class' => User::class,
            'label' => 'Alias address',
            'query_builder' => function (EntityRepository $er) use ($domain) {
                $qb = $er->createQueryBuilder('v');
                if ($domain===0) {
                  $qb
                      ->select('u.email')
                      ->from('VmailBundle:User', 'u')
                      //->where('u.domain = :domain')
                      ->andWhere('u.list = 0')
                      //->setParameter('domain', $domain)
                  ;
                } else {
                  $qb
                      ->select('u')
                      ->from('VmailBundle:User', 'u')
                      ->where('u.domain = :domain')
                      ->andWhere('u.list = 0')
                      ->setParameter('domain', $domain)
                  ;
                }
                return $qb;
            },
          ]
        )
        ;
        $builder
        ->add('active', CheckboxType::class,
          [
            'required' => false
          ]
        )
        ;
        $builder->get('active')
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
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'VmailBundle\Entity\Alias',
            'domain' => false,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_alias';
    }


}
