<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_configuration', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name')->unique();
            $table->string('type');
            $table->longText('value')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        $date = Carbon::now();

        DB::table('system_configuration')->insert([
            'key' => 'application_name',
            'name' => 'Nombre de la institución',
            'type' => 'string',
            'value' => null,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        DB::table('system_configuration')->insert([
            'key' => 'support_emails',
            'name' => 'Correos de soporte',
            'type' => 'array',
            'value' => null,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        DB::table('system_configuration')->insert([
            'key' => 'logo',
            'name' => 'Logo',
            'type' => 'string',
            'value' => null,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        DB::table('system_configuration')->insert([
            'key' => 'favicon',
            'name' => 'Favicon',
            'type' => 'string',
            'value' => null,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        DB::table('system_configuration')->insert([
            'key' => 'banner',
            'name' => 'Banner',
            'type' => 'string',
            'value' => null,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        DB::table('system_configuration')->insert([
            'key' => 'maximum_file_size_to_upload',
            'name' => 'Tamaño máximo de archivos a subir (MB)',
            'type' => 'number',
            'value' => 2,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        DB::table('system_configuration')->insert([
            'key' => 'study_days',
            'name' => 'Dias de estudio',
            'type' => 'array',
            'value' => '[1, 2, 3, 4, 5]',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        DB::table('system_configuration')->insert([
            'key' => 'study_hour_start',
            'name' => 'Inicio de las horas de estudio',
            'type' => 'string',
            'value' => '07:00',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        DB::table('system_configuration')->insert([
            'key' => 'study_hour_end',
            'name' => 'Fin de las horas de estudio',
            'type' => 'string',
            'value' => '21:00',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        DB::table('system_configuration')->insert([
            'key' => 'extensions_allowed_to_upload',
            'name' => 'Extensiones permitidas para subir archivos',
            'type' => 'array',
            'value' => json_encode([
                ["extension" => "pdf", "permitted" => true],
                ["extension" => "doc", "permitted" => true],
                ["extension" => "docx", "permitted" => true],
                ["extension" => "xls", "permitted" => true],
                ["extension" => "xlsx", "permitted" => true],
                ["extension" => "ppt", "permitted" => true],
                ["extension" => "pptx", "permitted" => true],

                ["extension" => "zip", "permitted" => true],
                ["extension" => "rar", "permitted" => true],

                ["extension" => "jpg", "permitted" => true],
                ["extension" => "jpeg", "permitted" => true],
                ["extension" => "png", "permitted" => true],
                ["extension" => "gif", "permitted" => true],

                ["extension" => "mp3", "permitted" => true],

                ["extension" => "mp4", "permitted" => true],
                ["extension" => "avi", "permitted" => true],
                ["extension" => "mkv", "permitted" => true],
            ]),
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        DB::table('system_configuration')->insert([
            'key' => 'landing_page',
            'name' => 'Landing Page',
            'type' => 'json',
            'value' => json_encode([
                'institution' => [
                    'name' => 'Nombre de instituto',
                    'description' => 'Somos sinónimo de la excelencia, somos una institución educativa de nivel superior dedicada a ofrecer una educación de alta calidad, orientada a la formación integral',
                    'logo' => 'default/logo.png',
                ],
                'banners' => [
                    'default/banner1.jpg',
                    'default/banner2.jpg',
                    'default/banner3.jpg'
                ],
                'services' => [
                    'title' => 'Algunos servicios que ofrecemos',
                    'description' => 'Tenemos una amplia gama de servicios, orientados a satisfacer las necesidades de nuestros alumnos.',
                    'arrServices' => [
                        [
                            'name' => 'Capacitación técnica especializada',
                            'description' => 'Cursos y programas de formación en diversas áreas técnicas.',
                            'icon' => 'settings'
                        ],
                        [
                            'name' => 'Certificación profesional',
                            'description' => 'Programas de certificación que validan las competencias y conocimientos.',
                            'icon' => 'certificate'
                        ],
                        [
                            'name' => 'Laboratorios de innovación',
                            'description' => 'Espacios equipados con tecnología de punta donde los estudiantes se desarrollan.',
                            'icon' => 'flask'
                        ],
                        [
                            'name' => 'Talleres prácticos y seminarios',
                            'description' => 'Sesiones prácticas y teóricas impartidas por expertos del sector.',
                            'icon' => 'tool'
                        ],
                        [
                            'name' => 'Bolsa de trabajo y prácticas',
                            'description' => 'Un servicio que conecta a estudiantes y egresados con oportunidades laborales.',
                            'icon' => 'affiliate-filled'
                        ],
                        [
                            'name' => 'Programas de educación continua',
                            'description' => 'Cursos cortos y modulares diseñados para profesionales.',
                            'icon' => 'git-compare'
                        ]
                    ]
                ],
                'news' => [
                    'title' => 'Conoce nuestras novedades',
                    'description' => 'Revisa las últimas novedades que tenemos para ofrecerte.',
                    'arrNews' => [
                        [
                            'icon' => 'world',
                            'title' => 'Programas de Intercambio',
                            'description' => 'Estudia en institutos tecnológicos internacionales y amplía tu visión global.'
                        ],
                        [
                            'icon' => 'school',
                            'title' => 'Charlas con Expertos',
                            'description' => 'Participa en conferencias y talleres con líderes del sector tecnológico.'
                        ],
                        [
                            'icon' => 'users-group',
                            'title' => 'Espacios de Coworking',
                            'description' => 'Colabora en proyectos con compañeros en un ambiente profesional y dinámico.'
                        ],
                        [
                            'icon' => 'building-factory-2',
                            'title' => 'Proyectos con Empresas',
                            'description' => 'Trabaja en proyectos reales con empresas locales y obtén experiencia práctica.'
                        ],
                        [
                            'icon' => 'chalkboard',
                            'title' => 'Aulas Interactivas',
                            'description' => 'Utiliza tecnología de punta en clases interactivas y participativas.'
                        ]
                    ]
                ],
                'teachers' => [
                    'title' => 'Conoce nuestros profesores',
                    'description' => 'Contamos con una gran cantidad de profesionales, altamente capacitados y con una amplia experiencia en el área.',
                    'arrTeachers' => [
                        [
                            'image' => 'default/teacher1.jpg',
                            'name' => 'Juan Perez',
                            'description' => 'Ing. químico'
                        ],
                        [
                            'image' => 'default/teacher2.jpg',
                            'name' => 'Laura Jimenez',
                            'description' => 'Tec. en enfermería'
                        ],
                        [
                            'image' => 'default/teacher3.jpg',
                            'name' => 'Julia Muñoz',
                            'description' => 'Mag. en educación'
                        ],
                        [
                            'image' => 'default/teacher4.jpg',
                            'name' => 'Fernanda Lizana',
                            'description' => 'Doc. en sistemas'
                        ]
                    ]
                ],
                'careers' => [
                    'title' => 'Contamos con estas carreras y más...',
                    'description' => 'Conoce las carreras que tenemos para ofrecerte.',
                    'arrCareers' => [
                        [
                            'name' => 'Ingeniería en Robótica y Automatización',
                            'icon' => 'tool',
                            'courses' => [
                                'Introducción a la Robótica',
                                'Sistemas de Control y Automatización',
                                'Programación de Robots',
                                'Sensores y Actuadores',
                                'Robótica Avanzada'
                            ]
                        ],
                        [
                            'name' => 'Desarrollo de Software y Aplicaciones',
                            'icon' => 'device-laptop',
                            'courses' => [
                                'Fundamentos de Programación',
                                'Desarrollo Web Frontend',
                                'Bases de Datos',
                                'Desarrollo de Aplicaciones Móviles',
                                'Metodologías Ágiles'
                            ]
                        ],
                        [
                            'name' => 'Ciberseguridad y Redes',
                            'icon' => 'lock',
                            'courses' => [
                                'Introducción a la Ciberseguridad',
                                'Administración de Redes',
                                'Seguridad en Redes',
                                'Criptografía Aplicada',
                                'Auditoría y Penetration Testing'
                            ]
                        ]
                    ]
                ],
                'summary' => [
                    [
                        'value' => '#1',
                        'label' => 'De la región',
                        'icon' => 'award'
                    ],
                    [
                        'value' => '+100',
                        'label' => 'Profesores',
                        'icon' => 'school'
                    ],
                    [
                        'value' => '+10000',
                        'label' => 'Alumnos inscritos',
                        'icon' => 'users'
                    ],
                    [
                        'value' => '+20',
                        'label' => 'Carreras',
                        'icon' => 'book'
                    ]
                ],
                'contact_information' => [
                    'phones' => [
                        'xxxxxxxxx',
                    ],
                    'emails' => [
                        'correo@ejemplo.com'
                    ]
                ]
            ]),
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        DB::table('system_configuration')->insert([
            'key' => 'primary_color',
            'name' => 'Color Primario',
            'type' => 'string',
            'value' => '#7367F0',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        DB::table('system_configuration')->insert([
            'key' => 'alerts_imports',
            'name' => 'Alerta de Importaciones',
            'type' => 'string',
            'value' => 'Realiza las importaciones pendientes con los archivos obtenidos desde Registra para acceder a más funcionalidades.',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        DB::table('system_configuration')->insert([
            'key' => 'virtual_library_url',
            'name' => 'Biblioteca Virtual',
            'type' => 'string',
            'value' => null,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        DB::table('system_configuration')->insert([
            'key' => 'institutional_repository_url',
            'name' => 'Repositorio Institucional',
            'type' => 'string',
            'value' => null,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_configuration');
    }
};
