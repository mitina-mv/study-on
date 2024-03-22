<?php

namespace App\Form;

use App\Entity\Course;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', null, [
                'label' => 'МногоСимвольный код',
                // TODO при использовании возникает доп ошибка (?)
                // текст ошибки: This value should be of type object.
                // 'constraints' => [
                //     new UniqueEntity(fields: 'code', message: 'Выберите другой символьный код'),
                // ],
            ])
            ->add('title', null, [
                'label' => 'Название',

            ])
            ->add('description', TextareaType::class, [
                'label' => 'Продающее описание',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}
