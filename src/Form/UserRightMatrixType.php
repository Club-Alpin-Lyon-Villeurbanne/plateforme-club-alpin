<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserRightMatrixType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $usertypes = $options['usertypes'];
        $userrights = $options['userrights'];
        $attributions = $options['attributions'];

        $choices = [];
        foreach ($userrights as $right) {
            foreach ($usertypes as $type) {
                $key = $type->getId() . '-' . $right->getId();
                $choices[$right->getTitle() . ' / ' . $type->getTitle()] = $key;
            }
        }

        $builder->add('attributions', ChoiceType::class, [
            'choices' => $choices,
            'multiple' => true,
            'expanded' => true,
            'data' => $attributions,
            'label' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'usertypes' => [],
            'userrights' => [],
            'attributions' => [],
        ]);
    }
}
