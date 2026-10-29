<?php

namespace ValidationContractFixtures;

final class ComparisonSizeBranches
{
    public function rules(): array
    {
        return [
            'gt' => ['gt:10'], 'gte' => ['gte:10'], 'lt' => ['lt:0'], 'lte' => ['lte:0'],
            'before' => ['max:5', 'gt:10'], 'after' => ['lt:0', 'min:5'],
            'text' => ['bail', 'string', 'max:5', 'gt:10'],
            'upload' => ['bail', 'file', 'max:5', 'gt:10'],
            'items' => ['array', 'min:5', 'gt:10'],
            'complete' => ['bail', 'string', 'max:5', 'gt:10'],
            'bailedArray' => ['bail', 'array', 'gt:10'], 'bailedFile' => ['bail', 'file', 'gt:10'],
        ];
    }
    public function messages(): array
    {
        return [
            'gt.gt' => ['string' => 'String.', 'file' => 'File.'],
            'gte.gte' => ['string' => 'String.', 'file' => 'File.'],
            'lt.lt' => ['string' => 'String.', 'file' => 'File.'],
            'lte.lte' => ['string' => 'String.', 'file' => 'File.'],
            'before.max' => ['string' => 'String.', 'file' => 'File.'], 'before.gt' => 'Greater.',
            'after.lt' => 'Less.', 'after.min' => ['string' => 'String.', 'file' => 'File.'],
            'text.string' => 'String.', 'text.max' => ['string' => 'String.'], 'text.gt' => 'Greater.',
            'upload.file' => 'File.', 'upload.max' => ['file' => 'File.'], 'upload.gt' => 'Greater.',
            'items.array' => 'Array.', 'items.min' => ['array' => 'Array.'], 'items.gt' => 'Greater.',
            'complete.string' => 'String.', 'complete.max' => ['string' => 'String.', 'numeric' => 'Number.'], 'complete.gt' => 'Greater.',
            'bailedArray.array' => 'Array.', 'bailedArray.gt' => ['array' => 'Array.'],
            'bailedFile.file' => 'File.', 'bailedFile.gt' => ['file' => 'File.'],
        ];
    }
}
