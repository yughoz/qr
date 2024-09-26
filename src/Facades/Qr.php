<?php

namespace LaraZeus\Qr\Facades;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\HtmlString;
use SimpleSoftwareIO\QrCode\Generator;
use Illuminate\Http\UploadedFile;

class Qr extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'qr';
    }

    public static function getDefaultOptions(): array
    {
        return [
            'size' => '300',
            'type' => 'png',
            'margin' => '1',
            'color' => 'rgba(74, 74, 74, 1)',
            'back_color' => 'rgba(252, 252, 252, 1)',
            'style' => 'square',
            'hasGradient' => false,
            'gradient_form' => 'rgb(69, 179, 157)',
            'gradient_to' => 'rgb(241, 148, 138)',
            'gradient_type' => 'vertical',
            'hasEyeColor' => false,
            'eye_color_inner' => 'rgb(241, 148, 138)',
            'eye_color_outer' => 'rgb(69, 179, 157)',
            'eye_style' => 'square',
        ];
    }

    public static function getFormSchema(
        string $statePath,
        string $optionsStatePath,
        ?string $defaultUrl = 'https://',
        bool $showUrl = true
    ): array {
        return [
            TextInput::make($statePath)
                ->live(onBlur: true)
                ->formatStateUsing(fn ($state) => $state ?? $defaultUrl)
                ->visible($showUrl),

            Grid::make()
                ->schema([
                    Section::make()
                        ->id('main-card')
                        ->columns(['sm' => 2])
                        ->columnSpan(['sm' => 2, 'lg' => 1])
                        ->statePath($optionsStatePath)
                        ->schema([
                            Hidden::make('type')->default('png'),

                            TextInput::make('size')
                                ->live()
                                ->default(300)
                                ->numeric()
                                ->label(__('Size')),

                            Select::make('margin')
                                ->live()
                                ->default(1)
                                ->label(__('Margin'))
                                ->selectablePlaceholder(false)
                                ->options([
                                    '0' => '0',
                                    '1' => '1',
                                    '3' => '3',
                                    '7' => '7',
                                    '9' => '9',
                                ]),

                            ColorPicker::make('color')
                                ->live()
                                ->default('rgba(74, 74, 74, 1)')
                                ->label(__('Color'))
                                ->rgba(),

                            ColorPicker::make('back_color')
                                ->live()
                                ->default('rgba(252, 252, 252, 1)')
                                ->label(__('Back Color'))
                                ->rgba(),

                            Select::make('style')
                                ->selectablePlaceholder(false)
                                ->live()
                                ->columnSpanFull()
                                ->label(__('Style'))
                                ->default('square')
                                ->options([
                                    'square' => __('square'),
                                    'round' => __('round'),
                                    'dot' => __('dot'),
                                ]),

                            Toggle::make('hasGradient')
                                ->live()
                                ->inline()
                                ->default(false)
                                ->columnSpanFull()
                                ->reactive()
                                ->label(__('Gradient')),

                            Grid::make()
                                ->schema([
                                    ColorPicker::make('gradient_form')
                                        ->live()
                                        ->default('rgb(69, 179, 157)')
                                        ->label(__('Gradient From'))
                                        ->rgb(),

                                    ColorPicker::make('gradient_to')
                                        ->live()
                                        ->default('rgb(241, 148, 138)')
                                        ->label(__('Gradient To'))
                                        ->rgb(),

                                    Select::make('gradient_type')
                                        ->selectablePlaceholder(false)
                                        ->columnSpanFull()
                                        ->default('vertical')
                                        ->live()
                                        ->label(__('Gradient Type'))
                                        ->options([
                                            'vertical' => __('vertical'),
                                            'horizontal' => __('horizontal'),
                                            'diagonal' => __('diagonal'),
                                            'inverse_diagonal' => __('inverse_diagonal'),
                                            'radial' => __('radial'),
                                        ]),
                                ])
                                ->columnSpan(['sm' => 2])
                                ->columns(['sm' => 2])
                                ->visible(fn (Get $get) => $get('hasGradient')),

                            Toggle::make('hasEyeColor')
                                ->live()
                                ->inline()
                                ->columnSpanFull()
                                ->default(false)
                                ->label(__('Eye Config')),

                            Grid::make()
                                ->schema([
                                    ColorPicker::make('eye_color_inner')
                                        ->live()
                                        ->default('rgb(241, 148, 138)')
                                        ->label(__('Inner Eye Color'))
                                        ->rgb(),

                                    ColorPicker::make('eye_color_outer')
                                        ->live()
                                        ->default('rgb(69, 179, 157)')
                                        ->label(__('Outer Eye Color'))
                                        ->rgb(),

                                    Select::make('eye_style')
                                        ->columnSpanFull()
                                        ->selectablePlaceholder(false)
                                        ->live()
                                        ->default('square')
                                        ->label(__('Eye Style'))
                                        ->options([
                                            'square' => __('square'),
                                            'circle' => __('circle'),
                                        ]),
                                ])
                                ->columnSpan(['sm' => 2])
                                ->columns(['sm' => 2])
                                ->visible(fn (Get $get) => $get('hasEyeColor')),

                            FileUpload::make('logo')
                                ->live()
                                ->imageEditor()
                                ->columnSpanFull()
                                ->image(),
                        ]),

                    Placeholder::make('preview')
                        ->label(__('Preview'))
                        ->columns(['sm' => 2])
                        ->columnSpan(['sm' => 2, 'lg' => 1])
                        ->key('preview_placeholder')
                        ->content(fn (Get $get) => Qr::render(
                            data: $get($statePath),
                            options: $get($optionsStatePath),
                            statePath: $statePath,
                            optionsStatePath: $optionsStatePath
                        )),
                ]),
        ];
    }

    // @internal
    public static function output(?string $data = null, ?array $options = null): HtmlString
    {
        $maker = new Generator;

        $options = $options ?? Qr::getDefaultOptions();

        call_user_func_array(
            [$maker, 'color'],
            ColorManager::getColorAsArray($options, 'color')
        );

        call_user_func_array(
            [$maker, 'backgroundColor'],
            ColorManager::getColorAsArray($options, 'back_color')
        );

        $maker = $maker->size(filled($options['size']) ? $options['size'] : static::getDefaultOptions()['size']);

        if ($options['hasGradient']) {
            if (filled($options['gradient_to']) && filled($options['gradient_form'])) {
                $gradient_form = ColorManager::getColorAsArray($options, 'gradient_form');
                $gradient_to = ColorManager::getColorAsArray($options, 'gradient_to');

                $gradientOptions = array_merge($gradient_to, $gradient_form, [$options['gradient_type']]);
                call_user_func_array([$maker, 'gradient'], $gradientOptions);
            }
        }

        if ($options['hasEyeColor']) {
            if (filled($options['eye_color_inner']) && filled($options['eye_color_outer'])) {
                $eye_color_inner = ColorManager::getColorAsArray($options, 'eye_color_inner');
                $eye_color_outer = ColorManager::getColorAsArray($options, 'eye_color_outer');

                $eyeColorInnerOptions = array_merge([0], $eye_color_inner, $eye_color_outer);
                call_user_func_array([$maker, 'eyeColor'], $eyeColorInnerOptions);

                $eyeColorInnerOptions = array_merge([1], $eye_color_inner, $eye_color_outer);
                call_user_func_array([$maker, 'eyeColor'], $eyeColorInnerOptions);

                $eyeColorInnerOptions = array_merge([2], $eye_color_inner, $eye_color_outer);
                call_user_func_array([$maker, 'eyeColor'], $eyeColorInnerOptions);
            }
        }

        if (filled($options['margin'])) {
            $maker = $maker->margin($options['margin']);
        }

        if (filled($options['style'])) {
            $maker = $maker->style($options['style']);
        }

        if (filled($options['eye_style'])) {
            $maker = $maker->eye($options['eye_style']);
        }

        if (optional($options)['logo']) {
            reset($options['logo']);
            $logo = current($options['logo']);
            

            if ($logo instanceof \Illuminate\Http\UploadedFile && filled($logo->getPathName())) {
                $maker = $maker->merge($logo->getPathName(), .4, true);
            } else {
                $filePath = storage_path('app/public/' . $logo);

                if (file_exists($filePath)) {
                    $logo = new UploadedFile(
                        $filePath, 
                        $logo, 
                        mime_content_type($filePath), 
                        null, 
                        true // This flag indicates the file is already moved to its final location
                    );
                    $maker = $maker->merge($logo->getPathName(), .4, true);
                } 
            }
        }

        return new HtmlString(
            // @phpstan-ignore-next-line
            $maker->format(optional($options)['type'] ?? 'png')
                ->generate((filled($data) ? $data : 'https://'))
                ->toHtml()
        );
    }

    public static function render(
        ?string $data = null,
        ?array $options = null,
        string $statePath = 'url',
        string $optionsStatePath = 'options',
        bool $downloadable = true
    ): HtmlString {
        return new HtmlString(
            view('zeus-qr::download', [
                'optionsStatePath' => $optionsStatePath,
                'statePath' => $statePath,
                'data' => $data,
                'options' => $options ?? Qr::getDefaultOptions(),
                'downloadable' => $downloadable,
            ])->render()
        );
    }
}
