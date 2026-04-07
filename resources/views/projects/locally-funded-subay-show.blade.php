@extends('layouts.dashboard')

@section('title', 'SubayBAYAN Project Profile')
@section('page-title', 'SubayBAYAN Project Profile')

@section('content')
    <div class="content-header">
        <h1>SubayBAYAN Project Profile</h1>
        <p>Read-only view from SubayBAYAN data.</p>
    </div>

    <div style="margin-bottom: 16px;">
        <a href="{{ route('projects.locally-funded') }}" style="padding: 8px 16px; background-color: #002C76; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13px;">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    @php
        $sanitizeCell = function ($value) {
            if ($value === null) {
                return '-';
            }
            $string = (string) $value;
            if ($string === '') {
                return '-';
            }
            if (function_exists('mb_convert_encoding')) {
                $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8,ISO-8859-1,WINDOWS-1252');
            } elseif (function_exists('utf8_encode')) {
                $string = utf8_encode($string);
            }
            if (function_exists('iconv')) {
                $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $string);
                if ($clean !== false) {
                    $string = $clean;
                }
            }
            return $string;
        };
    @endphp

    <div style="background: white; padding: 24px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px;">
            <h2 style="color: #002C76; font-size: 18px; margin: 0;">
                {{ $sanitizeCell($project->project_code ?? '') }}
            </h2>
            <div style="color: #374151; font-size: 14px;">
                {{ $sanitizeCell($project->project_title ?? '') }}
            </div>
        </div>

        <div style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                <tbody>
                    @foreach($columns as $column)
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 10px 12px; font-weight: 600; color: #374151; width: 30%; background-color: #f9fafb;">
                                {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $column)) }}
                            </td>
                            <td style="padding: 10px 12px; color: #374151;">
                                {{ $sanitizeCell($project->$column ?? null) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
