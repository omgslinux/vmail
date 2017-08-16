<?php

namespace VmailBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use VmailBundle\Entity\User;
use Symfony\Component\Form\CallbackTransformer;
use Doctrine\ORM\EntityRepository;

class AliasType extends AbstractType
{
    private $type='aliases';
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $domain=(!empty($options['domain'])?$options['domain']:false);
        $this->type=($options['showVirtual']?'virtuals':'aliases');
        $builder
        ->add('name', EntityType::class,
          [
            'class' => User::class,
            'label' => 'Email address',
            'query_builder' => function (EntityRepository $er) use ($domain) {
                $qb = $er->createQueryBuilder('v');
                $qb
                    ->select('u')
                    ->from('VmailBundle:User', 'u')
                    //->join('VmailBundle:Virtual', 'v', 'WITH', 'u.id=v.name')
                    ->where('u.domain = :domain')
                    ->andWhere('u.list = 0')
                    ->setParameter('domain', $domain)
                ;
                return $qb;
            },
          ]
        )
        ;
        $builder
        ->add('active')
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
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            //'data_class' => 'VmailBundle\Entity\Alias',
            'showVirtual' => false,
            'domain' => false,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_'. $this->type;
    }


}
