<?php

namespace Modules\Shared\Enum;

class EmailBodyTemplate
{
    const credentials = '
    <div class="container">
        <h1>Bienvenido a la Institución {{institutionName}}</h1>
        <p>Hola {{name}}</p>
        <p>Te enviamos las credenciales para acceder a tu Institución: <a href="{{institutionUrl}}">{{institutionName}}</a></p>
        <ul>
            <li><strong>Correo:</strong> {{email}}</li>
            <li><strong>Contraseña:</strong> {{password}}</li>
        </ul>
        <p>Te recomendamos cambiar tu contraseña desde tu perfil, puedes hacer esto en cualquier momento.</p>
    </div>
    ';

    const createContent = '
    <div class="container">
        <h1>Hola {{name}}</h1>
        <h4>¡Te notificamos la disponibilidad de nuevo contenido!</h4>
        <hr>
        <p><strong>Curso:</strong> {{courseName}}</p>
        <p><strong>Docente:</strong> {{teacherName}}</p>
    </div>
    ';

    const createTrainingContent = '
    <div class="container">
        <h1>Hola {{name}}</h1>
        <h4>¡Te notificamos la disponibilidad de nuevo contenido!</h4>
        <hr>
        <p><strong>Capacitación:</strong> {{trainingName}}</p>
        <p><strong>Docente:</strong> {{teacherName}}</p>
    </div>
    ';

    const updateContent = '
    <div class="container">
        <h1>Hola {{name}}</h1>
        <h4>¡Te notificamos que se ah actualizado contenido!</h4>
        <hr>
        <p><strong>Curso:</strong> {{courseName}}</p>
        <p><strong>Docente:</strong> {{teacherName}}</p>
    </div>
    ';

    const updateTrainingContent = '
    <div class="container">
        <h1>Hola {{name}}</h1>
        <h4>¡Te notificamos que se ah actualizado contenido!</h4>
        <hr>
        <p><strong>Capacitación:</strong> {{trainingName}}</p>
        <p><strong>Docente:</strong> {{teacherName}}</p>
    </div>
    ';

    const deliveredAnswer = '
    <div class="container">
        <h1>Hola {{name}}</h1>
        <h4>¡Te notificamos que hay nuevas respuestas en el contenido!</h4>
        <hr>
        <p><strong>Curso:</strong> {{courseName}}</p>
        <p><strong>Estudiante:</strong> {{studentName}}</p>
    </div>
    ';

    const deliveredTrainingAnswer = '
    <div class="container">
        <h1>Hola {{name}}</h1>
        <h4>¡Te notificamos que hay nuevas respuestas en el contenido!</h4>
        <hr>
        <p><strong>Capacitación:</strong> {{trainingName}}</p>
        <p><strong>Estudiante:</strong> {{studentName}}</p>
    </div>
    ';

    const resetPassword = '
    <div class="container">
        <h1>Hola {{name}}</h1>
        <p>Solicitaste un cambio de contraseña para el correo: {{email}}</p>
        <hr>
        <p>Puedes cambiar tu contraseña en el siguiente enlace:</p>
        <p><a href="{{url}}">Restablecer contraseña</a></p>
    </div>
    ';

  const applicantToCompany = '
    <div class="container" style="font-family: Arial, sans-serif; color: #333;">
      <h2>📥 Nueva postulación recibida</h2>

      <p>Un postulante ha aplicado a una de tus ofertas laborales.</p>

      <hr>

      <p><strong>Oferta:</strong> {{offer_title}}</p>
      <p><strong>Nombre del postulante:</strong> {{applicant_name}}</p>
      <p><strong>Correo:</strong> {{applicant_email}}</p>
      <p><strong>Teléfono:</strong> {{applicant_phone}}</p>
      <p><strong>CV:</strong> <a href="{{cv_url}}">Ver CV</a></p>

      <br>

      <p>Puedes ver la postulación completa aquí:</p>
      <p><a href="{{offer_url}}">{{offer_url}}</a></p>

      <hr>

      <p style="font-size: 12px; color: #888;">Este es un mensaje automático de la plataforma  Latina Educa - Bolsa Laboral. No respondas a este correo.</p>
    </div>
    ';

    const applicantConfirmation = '
      <div class="container" style="font-family: Arial, sans-serif; color: #333;">
        <h2>✅ Tu postulación fue enviada con éxito</h2>

        <p>Hola {{applicant_name}},</p>

        <p>Gracias por postular a la oferta <strong>{{offer_title}}</strong>.</p>

        <p>La empresa <strong>{{company_name}}</strong> ha recibido tu postulación y podrá revisar tus datos en cualquier momento.</p>

        <p>📎 Tu CV enviado: <a href="{{cv_url}}">Ver CV</a></p>

        <br>

        <p>¡Te deseamos mucho éxito!</p>

        <hr>

        <p style="font-size: 12px; color: #888;">Este es un mensaje automático de la plataforma  Latina Educa - Bolsa Laboral. No respondas a este correo.</p>
      </div>
      ';

    const companyCredentials = '
  <div class="container" style="font-family: Arial, sans-serif; color: #333;">
    <h1>Bienvenido a la Bolsa Laboral de {{institutionName}}</h1>

    <p>Hola {{companyName}},</p>

    <p>Tu empresa ha sido registrada exitosamente en la plataforma de bolsa laboral. A continuación te compartimos las credenciales de acceso:</p>

    <ul style="line-height: 1.6;">
      <li><strong>URL de acceso:</strong> <a href="{{loginUrl}}">{{loginUrl}}</a></li>
      <li><strong>Correo electrónico:</strong> {{email}}</li>
      <li><strong>Contraseña:</strong> {{password}}</li>
    </ul>

    <p>Te recomendamos cambiar la contraseña al iniciar sesión por primera vez.</p>

    <hr style="margin: 24px 0;" />

    <p>Si no reconoces este registro, por favor comunícate con el administrador del sistema.</p>

    <p style="font-size: 12px; color: #888;">Este es un mensaje automático, por favor no responder.</p>
  </div>
  ';

  const applicantDecision = '
<div class="container" style="font-family: Arial, sans-serif; color: #333;">
  <h2>{{status_icon}} Resultado de tu postulación</h2>

  <p>Hola {{applicant_name}},</p>

  <p>Queremos informarte sobre el estado de tu postulación a la siguiente oferta:</p>

  <p><strong>{{offer_title}}</strong></p>

  <hr>

  <p>{{message}}</p>

  <div style="margin-top: 20px; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #2196F3;">
    <p><strong>📣 Comentario de la empresa ({{company_name}}):</strong></p>
    <p>{{feedback}}</p>
  </div>

  <p style="margin-top: 20px;">Puedes ver más detalles iniciando sesión en la plataforma o contactando directamente con la empresa.</p>

  <hr>

  <p style="font-size: 12px; color: #888;">Este es un mensaje automático de la plataforma Latina Educa - Bolsa Laboral. No respondas a este correo.</p>
</div>
';

}
