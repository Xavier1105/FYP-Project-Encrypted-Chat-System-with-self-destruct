// crypto.js - Sentinel End-to-End Encryption Engine

    document.addEventListener("DOMContentLoaded", async () => {
        // 1. Check if the browser actually has your secret key
        const storedPrivateKey = localStorage.getItem("sentinel_private_key_" + MY_USER_ID);

        if (storedPrivateKey && typeof HAS_PUBLIC_KEY !== 'undefined' && !HAS_PUBLIC_KEY) {
            // Only regenerate if they HAVE a local private key, but the database lost the public key.
            console.log("Database missing Public Key. Regenerating...");
            await generateAndStoreKeys();
        } 
        else if (storedPrivateKey) {
            console.log("Secure E2EE Keys are perfectly synced.");
        }
        else {
            // We removed the auto-generation here! 
            // If they don't have a key, it just stays quiet and lets secure_gate.php handle the UI.
            console.log("No Private Key found in browser memory. Waiting for user input at Secure Gate...");
        }
    });
    
    /**
     * Generates the RSA Key Pair, saves the Private Key locally, and sends the Public Key to the server.
     */
    async function generateAndStoreKeys() {
        try {
            // 1. Generate the RSA-OAEP Key Pair (2048-bit)
            const keyPair = await window.crypto.subtle.generateKey(
                {
                    name: "RSA-OAEP",
                    modulusLength: 2048,
                    publicExponent: new Uint8Array([1, 0, 1]),
                    hash: "SHA-256",
                },
                true, // extractable so we can save it
                ["encrypt", "decrypt"]
            );

            // 2. Export keys to Base64 format so they can be stored as text
            const publicKeyBase64 = await exportCryptoKey(keyPair.publicKey);
            const privateKeyBase64 = await exportCryptoKey(keyPair.privateKey);

            // 3. SECURE STORAGE: Save Private Key strictly in the browser (NEVER send to server!)
            localStorage.setItem("sentinel_private_key_" + MY_USER_ID, privateKeyBase64);

            // 4. Send the Public Key to the database so others can encrypt messages for you
            const response = await fetch('save_public_key.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ public_key: publicKeyBase64 })
            });
            
            const result = await response.json();
            if (result.success) {
                console.log("E2EE Key Pair generated and secured successfully!");
            }

        } catch (error) {
            console.error("Critical E2EE Error: Failed to generate keys.", error);
        }
    }

    /**
     * Helper function: Converts a complex browser CryptoKey object into a standard Base64 string.
     */
    async function exportCryptoKey(key) {
        const exported = await window.crypto.subtle.exportKey(
            key.type === "public" ? "spki" : "pkcs8",
            key
        );
        const exportedAsString = String.fromCharCode.apply(null, new Uint8Array(exported));
        return window.btoa(exportedAsString);
    }

    /**
     * Helper function: Converts a Base64 string back into raw binary data.
     */
    function base64ToArrayBuffer(base64) {
        const binaryString = window.atob(base64);
        const bytes = new Uint8Array(binaryString.length);
        for (let i = 0; i < binaryString.length; i++) {
            bytes[i] = binaryString.charCodeAt(i);
        }
        return bytes.buffer;
    }

    /**
     * ENCRYPTION ENGINE: Locks a plaintext message using the receiver's Public Key.
     */
    async function encryptMessage(plainText, publicKeyBase64) {
        try {
            // 1. Convert their Base64 padlock back into a working Web Crypto object
            const publicKeyBuffer = base64ToArrayBuffer(publicKeyBase64);
            const publicKey = await window.crypto.subtle.importKey(
                "spki",
                publicKeyBuffer,
                { name: "RSA-OAEP", hash: "SHA-256" },
                false,
                ["encrypt"]
            );

            // 2. Convert your readable text into binary bytes
            const encoder = new TextEncoder();
            const encodedText = encoder.encode(plainText);

            // 3. MATHEMATICAL LOCKING (The actual End-to-End Encryption)
            const encryptedBuffer = await window.crypto.subtle.encrypt(
                { name: "RSA-OAEP" },
                publicKey,
                encodedText
            );

            // 4. Convert the encrypted gibberish back to Base64 so we can save it safely in MySQL
            const encryptedArray = new Uint8Array(encryptedBuffer);
            const encryptedBase64 = window.btoa(String.fromCharCode.apply(null, encryptedArray));
            
            return encryptedBase64;
        } catch (error) {
            console.error("Encryption failed:", error);
            return null;
        }
    }

    /**
     * DECRYPTION ENGINE: Unlocks the ciphertext using your locally stored Private Key.
     */
    async function decryptMessage(cipherTextBase64, overridePrivateKey = null) {
        if (!cipherTextBase64) return "";
        try {
            // NEW: Use the override key if provided, otherwise use the normal daily key
            const privateKeyBase64 = overridePrivateKey || localStorage.getItem("sentinel_private_key_" + MY_USER_ID);
            if (!privateKeyBase64) throw new Error("Private key missing!");

            // 2. Convert it back to a working Web Crypto object
            const privateKeyBuffer = base64ToArrayBuffer(privateKeyBase64);
            const privateKey = await window.crypto.subtle.importKey(
                "pkcs8",
                privateKeyBuffer,
                { name: "RSA-OAEP", hash: "SHA-256" },
                false,
                ["decrypt"]
            );

            // 3. Convert the gibberish (Base64) back to binary
            const cipherBuffer = base64ToArrayBuffer(cipherTextBase64);

            // 4. MATHEMATICAL UNLOCKING
            const decryptedBuffer = await window.crypto.subtle.decrypt(
                { name: "RSA-OAEP" },
                privateKey,
                cipherBuffer
            );

            // 5. Convert the raw binary back into human-readable text!
            const decoder = new TextDecoder();
            return decoder.decode(decryptedBuffer);

        } catch (error) {
            console.error("Decryption failed:", error);
            throw error; // Let index.php catch it so it prints the corrupted warning
        }
    }

    /**
     * Safe Base64 encoder for massive files
     */
    function bufferToBase64(buffer) {
        let binary = '';
        const bytes = new Uint8Array(buffer);
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary);
    }

    /**
     * HYBRID ENCRYPTION: Uses AES for massive files, and RSA to lock the AES key!
     */
    async function encryptLargeMessage(plainText, publicKeyBase64) {
        try {
            // 1. Generate a random AES lock (can hold massive files)
            const aesKey = await window.crypto.subtle.generateKey({ name: "AES-GCM", length: 256 }, true, ["encrypt"]);
            const iv = window.crypto.getRandomValues(new Uint8Array(12));
            
            // 2. Lock the massive image with AES
            const encodedText = new TextEncoder().encode(plainText);
            const encryptedImage = await window.crypto.subtle.encrypt({ name: "AES-GCM", iv: iv }, aesKey, encodedText);

            // 3. Export the AES key and lock it with the Receiver's RSA Padlock
            const rawAesKey = await window.crypto.subtle.exportKey("raw", aesKey);
            const rsaPublicKey = await window.crypto.subtle.importKey("spki", base64ToArrayBuffer(publicKeyBase64), { name: "RSA-OAEP", hash: "SHA-256" }, false, ["encrypt"]);
            const encryptedAesKey = await window.crypto.subtle.encrypt({ name: "RSA-OAEP" }, rsaPublicKey, rawAesKey);

            // 4. Package it all up into a JSON string
            return JSON.stringify({
                key: bufferToBase64(encryptedAesKey),
                iv: bufferToBase64(iv),
                data: bufferToBase64(encryptedImage)
            });
        } catch (e) {
            console.error("Large Encryption Failed:", e);
            return null;
        }
    }

    /**
     * HYBRID DECRYPTION: Unlocks the AES key, then unlocks the image.
     */
        async function decryptLargeMessage(packageJsonStr, overridePrivateKey = null) {
        try {
            const pkg = JSON.parse(packageJsonStr);
            // NEW: Use the override key if provided, otherwise use the normal daily key
            const privateKeyBase64 = overridePrivateKey || localStorage.getItem("sentinel_private_key_" + MY_USER_ID);
            const rsaPrivateKey = await window.crypto.subtle.importKey("pkcs8", base64ToArrayBuffer(privateKeyBase64), { name: "RSA-OAEP", hash: "SHA-256" }, false, ["decrypt"]);

            // 2. Unlock the tiny AES key using the RSA Private Key
            const rawAesKey = await window.crypto.subtle.decrypt({ name: "RSA-OAEP" }, rsaPrivateKey, base64ToArrayBuffer(pkg.key));
            const aesKey = await window.crypto.subtle.importKey("raw", rawAesKey, { name: "AES-GCM" }, false, ["decrypt"]);

            // 3. Unlock the massive image using the AES key!
            const decryptedImage = await window.crypto.subtle.decrypt({ name: "AES-GCM", iv: new Uint8Array(base64ToArrayBuffer(pkg.iv)) }, aesKey, base64ToArrayBuffer(pkg.data));

            return new TextDecoder().decode(decryptedImage);
        } catch (e) {
            console.error("Large Decryption Failed:", e);
            throw e;
        }
    }

    /**
 * SECURITY FINGERPRINT: Generates a 4-digit human-readable code from a Key string.
 */
function generateKeyFingerprint(keyString) {
    if (!keyString) return "0000";
    let hash = 0;
    for (let i = 0; i < keyString.length; i++) {
        const char = keyString.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash; // Convert to 32bit integer
    }
    return Math.abs(hash % 10000).toString().padStart(4, '0');
}

/**
 * RECONSTRUCTION ENGINE: Derives a Public Key from a raw Private Key string.
 * This is used for background "Radar" checks to see if the local key is outdated.
 */
async function generatePublicKeyFromPrivate(privateKeyBase64) {
    try {
        // 1. Convert the Base64 Private Key back into a cryptographic object
        const binaryPriv = Uint8Array.from(atob(privateKeyBase64), c => c.charCodeAt(0));
        const importedKey = await window.crypto.subtle.importKey(
            "pkcs8",
            binaryPriv,
            { name: "RSA-OAEP", hash: "SHA-256" },
            true,
            ["decrypt"]
        );

        // 2. Export the matching Public Key components
        const jwk = await window.crypto.subtle.exportKey("jwk", importedKey);
        
        // 3. Re-import as a Public Key object to export the clean SPKI format
        delete jwk.d; delete jwk.p; delete jwk.q; delete jwk.dp; delete jwk.dq; delete jwk.qi;
        jwk.key_ops = ["encrypt"];
        
        const publicObject = await window.crypto.subtle.importKey(
            "jwk",
            jwk,
            { name: "RSA-OAEP", hash: "SHA-256" },
            true,
            ["encrypt"]
        );

        const exportedPublic = await window.crypto.subtle.exportKey("spki", publicObject);
        return btoa(String.fromCharCode(...new Uint8Array(exportedPublic)));

    } catch (e) {
        console.error("Key derivation failed:", e);
        return null;
    }
}