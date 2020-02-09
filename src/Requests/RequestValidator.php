<?php

namespace Alexusmai\LaravelFileManager\Requests;

use Alexusmai\LaravelFileManager\Services\ConfigService\ConfigRepository;
use Illuminate\Foundation\Http\FormRequest;
use Storage;

class RequestValidator extends FormRequest
{
    use CustomErrorMessage;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $config = resolve(ConfigRepository::class);

        return [
            'disk' => [
                'sometimes',
                'string',
                function ($attribute, $value, $fail) use($config) {
                    if (!in_array($value, $config->getDiskList()) ||
                        !array_key_exists($value, config('filesystems.disks'))
                    ) {
                        return $fail('diskNotFound');
                    }
                },
            ],
            'path' => [
                'sometimes',
                'string',
                'nullable',
                function ($attribute, $value, $fail) {
                    // If you're hidden directory file, ignoring the file.
                    if ($this->input('disk') == config('file-manager.gcs.driver')) {
                        $needle = $value . '/' . config('file-manager.gcs.hiddenDirectoryFile');
                        if ($value && in_array($needle, Storage::disk($this->input('disk'))->files($value))) {
                            return;
                        }
                    }

                    if ($value && !Storage::disk($this->input('disk'))->exists($value)
                    ) {
                        return $fail('pathNotFound');
                    }
                },
            ],
        ];
    }

    /**
     * Not found message
     *
     * @return array|\Illuminate\Contracts\Translation\Translator|string|null
     */
    public function message()
    {
        return 'notFound';
    }
}
