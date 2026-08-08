plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.android")
    id("org.jetbrains.kotlin.plugin.compose")
    id("com.google.gms.google-services")
}

android {
    namespace = "com.ssd.wear"
    compileSdk = 35

    defaultConfig {
        applicationId = "com.ssd.wear"
        minSdk = 30 // Wear OS 3+
        targetSdk = 35
        versionCode = 1
        versionName = "1.0"
    }

    buildFeatures {
        compose = true
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    kotlinOptions {
        jvmTarget = "17"
    }
}

dependencies {
    implementation(platform("androidx.compose:compose-bom:2024.09.00"))
    implementation("androidx.wear.compose:compose-material:1.4.0")
    implementation("androidx.wear.compose:compose-foundation:1.4.0")
    implementation("androidx.activity:activity-compose:1.9.2")
    implementation("androidx.lifecycle:lifecycle-runtime-ktx:2.8.6")
    implementation("androidx.core:core-splashscreen:1.0.1")

    // Recibir el token desde el celular emparejado
    implementation("com.google.android.gms:play-services-wearable:19.0.0")
    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-play-services:1.8.1")

    // Firebase Cloud Messaging (recibir el push)
    implementation(platform("com.google.firebase:firebase-bom:33.4.0"))
    implementation("com.google.firebase:firebase-messaging-ktx")

    // Registrar el fcm_token contra planeaciones-api
    implementation("com.squareup.retrofit2:retrofit:2.11.0")
    implementation("com.squareup.retrofit2:converter-gson:2.11.0")

    // Guardar el token de sesión de forma cifrada
    implementation("androidx.security:security-crypto:1.1.0-alpha06")

    debugImplementation("androidx.compose.ui:ui-tooling")
}
