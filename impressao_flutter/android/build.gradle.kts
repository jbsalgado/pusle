allprojects {
    repositories {
        google()
        mavenCentral()
    }
}

val newBuildDir: Directory = rootProject.layout.buildDirectory.dir("../../build").get()
rootProject.layout.buildDirectory.value(newBuildDir)

subprojects {
    val newSubprojectBuildDir: Directory = newBuildDir.dir(project.name)
    project.layout.buildDirectory.value(newSubprojectBuildDir)
}
subprojects {
    project.evaluationDependsOn(":app")
}

subprojects {
    // Apply Java 11 ONLY to Android Libraries
    plugins.withId("com.android.library") {
        extensions.configure<com.android.build.gradle.LibraryExtension> {
            compileOptions {
                sourceCompatibility = JavaVersion.VERSION_11
                targetCompatibility = JavaVersion.VERSION_11
            }
        }
    }
    
    // Apply Kotlin 11
    tasks.withType<org.jetbrains.kotlin.gradle.tasks.KotlinCompile> {
        kotlinOptions {
             jvmTarget = "11"
        }
    }
    
    // Attempt Auto-Namespace Injection for legacy libs (Safety Net)
    plugins.withId("com.android.library") {
        val extension = extensions.getByType(com.android.build.gradle.LibraryExtension::class.java)
        if (extension.namespace == null) {
             val manifestFile = file("src/main/AndroidManifest.xml")
             if (manifestFile.exists()) {
                 runCatching {
                     val manifestContent = manifestFile.readText()
                     val packageMatch = Regex("package=\"([^\"]+)\"").find(manifestContent)
                     if (packageMatch != null) {
                         extension.namespace = packageMatch.groupValues[1]
                     }
                 }
             }
        }
    }
}

tasks.register<Delete>("clean") {
    delete(rootProject.layout.buildDirectory)
}
