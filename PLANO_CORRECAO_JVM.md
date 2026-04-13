# Plano de Implementação - Correção de Incompatibilidade JVM Target

O build está falhando porque as tarefas de Java estão usando a versão 1.8 por padrão, enquanto as de Kotlin foram forçadas para 11 no `build.gradle.kts`. Isso gera um conflito de compatibilidade no Gradle.

## Alterações Propostas

### 1. Root Android Configuration
- **Arquivo**: `pulse_app/android/build.gradle.kts`
- **Ação**: Forçar a versão 11 para todas as tarefas de compilação Java e Kotlin em todos os subprojetos (plugins).

```kotlin
subprojects {
    // ...
    tasks.withType<JavaCompile>().configureEach {
        sourceCompatibility = "11"
        targetCompatibility = "11"
    }

    tasks.withType<org.jetbrains.kotlin.gradle.tasks.KotlinCompile>().configureEach {
        kotlinOptions {
            jvmTarget = "11"
        }
    }
}
```

## Verificação
- Executar `flutter build apk --release`.
