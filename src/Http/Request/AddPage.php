<?php

namespace FastDog\Menu\Http\Request;


use FastDog\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Добавление материала
 *
 * @package FastDog\Menu\Http\Request
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class AddPage extends FormRequest
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
            //'alias' => 'required',
        ];
    }

    /**
     * Сообшения о ошибках
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'Поле "Название" обязательно для заполнения.',
            'alias.required' => 'Поле "Псевдоним" обязательно для заполнения.',
        ];
    }
}
