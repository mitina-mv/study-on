<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Form\DataTransformer\CourseToEntityTransformer;
use App\Form\DataTransformer\CourseToIdTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LessonType extends AbstractType
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('content', TextareaType::class, [
                'required' => true,
                'label' => 'Содержание урока',
            ])
            ->add('serialNumber')
            ->add('course', EntityType::class, [
                'class' => Course::class,
                'choice_label' => 'title',
            ])
            ->add('course_id', HiddenType::class, [
                'data' => $options['course_id'],
                'required' => false,
                'mapped' => false,
            ]);
        ;
        // $builder->get('course_id')->addModelTransformer(new CourseToIdTransformer($this->entityManager));
        $builder->get('course')->addModelTransformer(new CourseToEntityTransformer($this->entityManager));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lesson::class,
            'course_id' => null,
        ]);
    }
}
