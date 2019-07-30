<?php

namespace FastDog\Menu\Request;

use FastDog\Menu\Users\Entity\User;
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
        return [
            'name' => 'required',
            'type' => 'required',
            //'menu_id' => 'required',
        ];
    }

    /**
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => trans('app.Поле "Название" обязательно для заполнения.'),
            'type.required' => trans('app.Поле "Тип меню" обязательно для заполнения.'),
            //'menu_id.required' => trans('app.Поле "Меню" обязательно для заполнения.'),
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
                    $validator->errors()->add('menu_id', trans('app.Поле "Меню" обязательно для заполнения.'));
                }
//                if ($input['depth'] == 1) {
//                    $this->merge([
//                        'menu_id' => $input['parent_id'],
//                    ]);
//                }
            }
        });

        return $validator;
    }
}
