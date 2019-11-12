<?php

namespace FastDog\Menu\Http\Request;


use FastDog\User\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Добавление меню
 *
 * @package FastDog\Menu\Request
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class AddMenu extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (!\Auth::guest()) {
            $user = \Auth::getUser();
            if ($user->type == User::USER_TYPE_ADMIN) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        // append menu items
        if ($this->input('append', 'N') === 'Y')
            return [];

        $rules = [
            'name' => 'required',
            'type' => 'required',
            'site_id' => 'required',
        ];

        if ($this->input('type.id') != 'menu::menu') {
            $rules['menu_id'] = 'required';
        }

        return $rules;
    }

    /**
     * @return array
     */
    public function messages()
    {
        // append menu items
        if ($this->input('append', 'N') === 'Y')
            return [];

        return [
            'name.required' => trans('menu::requests.add_menu.name.required'),
            'type.required' => trans('menu::requests.add_menu.type.required'),
            'menu_id.required' => trans('menu::requests.add_menu.menu_id.required'),
            'site_id.required' => trans('menu::requests.add_menu.site_id.required'),
        ];
    }


    /**
     * @return \Illuminate\Contracts\Validation\Validator|mixed
     */
    public function getValidatorInstance()
    {
        $validator = parent::getValidatorInstance();
        $validator->after(function () use ($validator) {
            $input = $this->all();
            if (isset($input['id']) && isset($input['depth'])) {
                if ($input['depth'] > 1 && $input['menu_id'] == null) {
                    $validator->errors()->add('menu_id', trans('menu::requests.add_menu.menu.required'));
                }
            }
        });

        return $validator;
    }
}
