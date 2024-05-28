<?php

namespace App\Helpers;

class CourseHelper
{
    public static $typeNames = [
        'free' => 'Бесплатный',
        'rent' => 'Аренда',
        'buy' => 'Платный'
    ];
    
    public static function merge(array $response, array $courses): array
    {
        $result = [];
        
        $responseMap = [];
        foreach ($response as $item) {
            $responseMap[$item['code']] = $item;
        }
        
        foreach ($courses as $course) {
            $code = $course->getCode();

            $item = array_merge(
                $course->toArray(),
                ['type' => 'free']
            );
            
            if (isset($responseMap[$code])) {
                $item['type'] = $responseMap[$code]['type'];

                if (isset($responseMap[$code]['price'])) {
                    $item['price'] = $responseMap[$code]['price'];
                }
            }

            $item['type_name'] = self::$typeNames[$item['type']];
            
            $result[] = $item;
        }
        
        return $result;
    }

    public static function addTransactions(array $courses, array $transactions): array
    {
        $result = [];

        $responseMap = [];
        foreach ($transactions as $item) {
            if (!isset($item['course_code'])){
                continue;
            }

            $responseMap[$item['course_code']] = $item;
        }

        foreach ($courses as $course) {
            if (is_object($course)){
                $course = $course->toArray();
            }

            $code = $course['code'];

            $item = array_merge(
                $course,
                [
                    'transaction' => isset($responseMap[$code]) ? $responseMap[$code] : null
                ]
            );

            $result[] = $item;
        }

        return $result;
    }
}
