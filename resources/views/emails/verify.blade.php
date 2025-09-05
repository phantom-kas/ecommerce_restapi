@include('partials.header')

<tr>
    <td style="padding:20px; font-family:Arial, sans-serif; color:#333;">
        <p>Hi {{ $name }},</p>
        <p>Thank you for signing up! Please verify your email by clicking the button below:</p>
        <p>
            <a href="{{ $verifyLink }}" style="display:inline-block; padding:10px 20px; background-color:#28a745; color:white; text-decoration:none; border-radius:5px;">Verify Email</a>
        </p>
    </td>
</tr>

@include('partials.footer')
