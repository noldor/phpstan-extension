<?php

namespace ValidationContractFixtures;

use Illuminate\Validation\Rule;

enum CatalogEnum: string { case Value = 'value'; }

final class BuiltinCatalog
{
    public function rules(): array
    {
        return [
            'accepted' => [Rule::accepted()],
            'activeUrl' => [Rule::activeUrl()],
            'base64' => [Rule::base64()],
            'boolean' => [Rule::boolean(true)],
            'declined' => [Rule::declined()],
            'filled' => [Rule::filled()],
            'hexColor' => [Rule::hexColor()],
            'json' => [Rule::json()],
            'list' => [Rule::list()],
            'macAddress' => [Rule::macAddress()],
            'missing' => [Rule::missing()],
            'present' => [Rule::present()],
            'prohibited' => [Rule::prohibited()],
            'required' => [Rule::required()],
            'ulid' => [Rule::ulid()],
            'acceptedIf' => [Rule::acceptedIf('enabled', true)],
            'arrayKeys' => [Rule::arrayKeys(['name'])],
            'can' => [Rule::can('update')],
            'confirmed' => [Rule::confirmed()->customField('confirmation')],
            'contains' => [Rule::contains(['value'])],
            'currentPassword' => [Rule::currentPassword()->guard('web')],
            'declinedIf' => [Rule::declinedIf('enabled', true)],
            'distinct' => [Rule::distinct()->strict()],
            'doesntContain' => [Rule::doesntContain(['value'])],
            'enum' => [Rule::enum(CatalogEnum::class)],
            'exists' => [Rule::exists('users', 'id')],
            'in' => [Rule::in(['value'])],
            'inArray' => [Rule::inArray('other.*')],
            'inArrayKeys' => [Rule::inArrayKeys(['name'])],
            'missingIf' => [Rule::missingIf('enabled', true)],
            'missingUnless' => [Rule::missingUnless('enabled', true)],
            'missingWith' => [Rule::missingWith(['other'])],
            'missingWithAll' => [Rule::missingWithAll(['other'])],
            'notIn' => [Rule::notIn(['value'])],
            'notRegex' => [Rule::notRegex('/forbidden/')],
            'presentIf' => [Rule::presentIf('enabled', true)],
            'presentUnless' => [Rule::presentUnless('enabled', true)],
            'presentWith' => [Rule::presentWith(['other'])],
            'presentWithAll' => [Rule::presentWithAll(['other'])],
            'prohibitedIfAccepted' => [Rule::prohibitedIfAccepted('enabled')],
            'prohibitedIfDeclined' => [Rule::prohibitedIfDeclined('enabled')],
            'prohibits' => [Rule::prohibits(['other'])],
            'regex' => [Rule::regex('/expected/')],
            'requiredArrayKeys' => [Rule::requiredArrayKeys(['name'])],
            'requiredIfAccepted' => [Rule::requiredIfAccepted('enabled')],
            'requiredIfDeclined' => [Rule::requiredIfDeclined('enabled')],
            'requiredWith' => [Rule::requiredWith(['other'])],
            'requiredWithAll' => [Rule::requiredWithAll(['other'])],
            'requiredWithout' => [Rule::requiredWithout(['other'])],
            'requiredWithoutAll' => [Rule::requiredWithoutAll(['other'])],
            'timezone' => [Rule::timezone()],
            'unique' => [Rule::unique('users', 'name')],
            'url' => [Rule::url()->protocols(['https'])],
            'uuid' => [Rule::uuid()->version(4)],
            'anyOf' => [Rule::anyOf([['string'], ['numeric']])],
            'controls' => [Rule::excludeWith('other'), Rule::excludeWithout('other'), Rule::excludeIf(true), Rule::excludeUnless(false), Rule::bail(), Rule::nullable(), Rule::sometimes()],
        ];
    }
}
