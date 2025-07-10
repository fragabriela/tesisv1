<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Alumno;
use App\Models\Tutor;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ProyectoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'crear proyectos']);
        Permission::create(['name' => 'ver proyectos']);
        Permission::create(['name' => 'configurar proyectos']);
        
        // Create a role with permissions
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo('crear proyectos');
        $role->givePermissionTo('ver proyectos');
        $role->givePermissionTo('configurar proyectos');
        
        // Create a user with the role
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');
    }

    /** @test */
    public function it_validates_estado_field_correctly()
    {
        $this->withoutExceptionHandling();
        
        // Create dependencies
        $alumno = Alumno::create([
            'nombre' => 'Test',
            'apellido' => 'Student',
            'email' => 'test@example.com',
            'telefono' => '123456789',
            'cedula' => 'TEST123',
            'matricula' => 'MAT123',
            'fecha_nacimiento' => '2000-01-01',
            'id_carrera' => 1, // This would normally need to be created first
            'estado' => 'activo'
        ]);

        $tutor = Tutor::create([
            'nombre' => 'Test',
            'apellido' => 'Tutor',
            'email' => 'tutor@example.com',
            'telefono' => '987654321',
            'especialidad' => 'Testing',
            'activo' => true
        ]);
        
        // Login as the admin user
        $this->actingAs($this->user);
        
        // Valid data
        $data = [
            'titulo' => 'Test Proyecto',
            'descripcion' => 'This is a test description',
            'fecha_inicio' => now()->format('Y-m-d'),
            'fecha_fin' => now()->addMonth()->format('Y-m-d'),
            'alumno_id' => $alumno->id,
            'tutor_id' => $tutor->id,
            'estado' => 'en_progreso',
            'observaciones' => 'Test observations',
            'is_visible' => true,
            'github_repo' => 'https://github.com/test/repo.git',
        ];
        
        // Test valid submission
        $response = $this->post(route('proyectos.store'), $data);
        $response->assertStatus(302); // Should redirect
        $this->assertDatabaseHas('tesis', [
            'titulo' => 'Test Proyecto',
            'estado' => 'en_progreso'
        ]);
        
        // Test invalid estado value
        $data['estado'] = 'en progreso'; // with space instead of underscore
        $response = $this->post(route('proyectos.store'), $data);
        $response->assertSessionHasErrors('estado');
    }
}
