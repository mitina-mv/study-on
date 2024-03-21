<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use App\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;

class CourseToEntityTransformer implements DataTransformerInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function transform($course): ?int
    {
        if (null === $course) {
            return null;
        }

        return $course->getId();
    }

    public function reverseTransform($courseId): ?Course
    {
        if (!$courseId) {
            return null;
        }

        $course = $this->entityManager->getRepository(Course::class)->find($courseId);

        if (null === $course) {
            throw new TransformationFailedException(sprintf('The course with id "%s" does not exist!', $courseId));
        }

        return $course;
    }
}