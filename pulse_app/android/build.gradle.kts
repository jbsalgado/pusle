
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
    plugins.withId("com.android.library") {
        val android = extensions.findByName("android")
        if (android != null) {
            try {
                val getNamespace = android.javaClass.getMethod("getNamespace")
                if (getNamespace.invoke(android) == null) {
                    val ns = when (project.name) {
                        "flutter_bluetooth_serial" -> "io.github.edufolly.flutterbluetoothserial"
                        else -> "com.pulse.${project.name.replace('-', '_')}"
                    }
                    val setNamespace = android.javaClass.getMethod("setNamespace", String::class.java)
                    setNamespace.invoke(android, ns)
                }
            } catch (_: Exception) {}
        }
    }

    afterEvaluate {
        val android = extensions.findByName("android")
        if (android != null) {
            try {
                val m = android.javaClass.getMethod("setCompileSdkVersion", Int::class.javaPrimitiveType)
                m.invoke(android, 34)
            } catch (_: Exception) {
                try {
                    val m = android.javaClass.getMethod("setCompileSdk", java.lang.Integer::class.java)
                    m.invoke(android, 34)
                } catch (_: Exception) {
                    try {
                        val m = android.javaClass.getMethod("compileSdkVersion", Int::class.javaPrimitiveType)
                        m.invoke(android, 34)
                    } catch (_: Exception) {}
                }
            }
        }
    }
}

subprojects {
    project.evaluationDependsOn(":app")
}
 
subprojects {
    tasks.withType<org.jetbrains.kotlin.gradle.tasks.KotlinCompile>().configureEach {
        compilerOptions {
            jvmTarget.set(org.jetbrains.kotlin.gradle.dsl.JvmTarget.JVM_11)
        }
    }
 
    tasks.withType<JavaCompile>().configureEach {
        sourceCompatibility = "11"
        targetCompatibility = "11"
    }
}
 
tasks.register<Delete>("clean") {
    delete(rootProject.layout.buildDirectory)
}