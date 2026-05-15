// Copyright (c) 2025 Contributors to the Eclipse Foundation.
//
// See the NOTICE file(s) distributed with this work for additional
// information regarding copyright ownership.
//
// This program and the accompanying materials are made available under the
// terms of the Apache License, Version 2.0 which is available at
// https://www.apache.org/licenses/LICENSE-2.0
//
// SPDX-License-Identifier: Apache-2.0

/*
Compile with:
gcc scep.c -o scep -lcrypto

Compiled file should be moved to bin/scep (/var/www/application/bin/scep)

Packages needed for compilation:
 - apk add gcc openssl-dev g++
 - probably libstdc++-dev instead of g++ for edge (alpine 3.17)

Usage:
scep <selfSign.crt> <selfSign.key> <ca.public.key.pem> <csr> <outPEM file>

*/

#include <openssl/pem.h>
#include <openssl/pkcs7.h>
#include <openssl/err.h>
#include <openssl/evp.h>
#include <openssl/cms.h>

void rand_str(char *dest, size_t length)
{
    char charset[] = "0123456789"
                     "abcdefghijklmnopqrstuvwxyz"
                     "ABCDEFGHIJKLMNOPQRSTUVWXYZ";

    while (length-- > 0)
    {
        size_t index = (double)rand() / RAND_MAX * (sizeof charset - 1);
        *dest++ = charset[index];
    }
    *dest = '\0';
}

STACK_OF(X509_ATTRIBUTE) * generateScepAttributes()
{
    // Stack for SCEP attributes
    STACK_OF(X509_ATTRIBUTE) *scepAttributes = NULL;

    // Individual attribute - temporary variable
    X509_ATTRIBUTE *attribute = NULL;

    srand(time(NULL));

    scepAttributes = sk_X509_ATTRIBUTE_new_null();

    // Message Type
    int messageTypeNid = OBJ_create("2.16.840.1.113733.1.9.2", "messageType", "Message Type");
    ASN1_STRING *messageTypeValue = ASN1_STRING_new();
    ASN1_STRING_set(messageTypeValue, "19", 2);
    attribute = X509_ATTRIBUTE_create(messageTypeNid, V_ASN1_PRINTABLESTRING, messageTypeValue);
    sk_X509_ATTRIBUTE_push(scepAttributes, attribute);

    // PKI Status
    int pkiStatusNid = OBJ_create("2.16.840.1.113733.1.9.3", "pkiStatus", "PKI Status");
    ASN1_STRING *pkiStatusValue = ASN1_STRING_new();
    ASN1_STRING_set(pkiStatusValue, "3", 1);
    attribute = X509_ATTRIBUTE_create(pkiStatusNid, V_ASN1_PRINTABLESTRING, pkiStatusValue);
    sk_X509_ATTRIBUTE_push(scepAttributes, attribute);

    // Transaction ID
    char transactionId[17];
    rand_str(transactionId, 16);
    int transactionIDNid = OBJ_create("2.16.840.1.113733.1.9.7", "transactionID", "transactionID");
    ASN1_STRING *transactionIDValue = ASN1_STRING_new();
    ASN1_STRING_set(transactionIDValue, transactionId, strlen(transactionId));
    attribute = X509_ATTRIBUTE_create(transactionIDNid, V_ASN1_PRINTABLESTRING, transactionIDValue);
    sk_X509_ATTRIBUTE_push(scepAttributes, attribute);

    // Sender Nonce
    char senderNonce[17];
    rand_str(senderNonce, 16);
    int senderNonceNid = OBJ_create("2.16.840.1.113733.1.9.5", "senderNonce", "senderNonce");
    ASN1_OCTET_STRING *senderNonceValue = ASN1_OCTET_STRING_new();
    ASN1_OCTET_STRING_set(senderNonceValue, senderNonce, strlen(senderNonce));
    attribute = X509_ATTRIBUTE_create(senderNonceNid, V_ASN1_OCTET_STRING, senderNonceValue);
    sk_X509_ATTRIBUTE_push(scepAttributes, attribute);

    // Recipient Nonce
    char recipientNonce[17];
    rand_str(recipientNonce, 16);
    int recipientNonceNid = OBJ_create("2.16.840.1.113733.1.9.6", "recipientNonce", "recipientNonce");
    ASN1_OCTET_STRING *recipientNonceValue = ASN1_OCTET_STRING_new();
    ASN1_OCTET_STRING_set(recipientNonceValue, recipientNonce, strlen(recipientNonce));
    attribute = X509_ATTRIBUTE_create(recipientNonceNid, V_ASN1_OCTET_STRING, recipientNonceValue);
    sk_X509_ATTRIBUTE_push(scepAttributes, attribute);

    return scepAttributes;
}

/**
 * Function creates PKCS7 envelope with provided certificateSigningRequest.
 * Provided certificateSigningRequest is encrypted using receiverCertificate
 * so only holder of privateKey matching this certificate will be able to decrypt it.
 *
 * In this use case receiverCertificate is public certificate of CA that is requested to sign this CSR
 */
CMS_ContentInfo *getPkcs7EnvelopedAndEncryptedWithCertificateSigningRequest(X509 *receiverCertificate, X509_REQ *certificateSigningRequest, const EVP_CIPHER *cipher)
{
    // Final PKCS7 structure containing enveloped and encrypted certificate signing request
    CMS_ContentInfo *pkcs7EnvelopedAndEncryptedWithCertificateSigningRequest = NULL;

    // Stack of receiver certificates - in this case only one CA certificate - used for encryption of the certificate signing request
    STACK_OF(X509) *stackOfCertificates = NULL;

    // Block Input/Output Object for PKCS7 structure with signed certificate
    BIO *certificateSigningRequestBio = NULL;

    // Prepare BIO for signed certificate PKCS7 structure
    certificateSigningRequestBio = BIO_new(BIO_s_mem());
    if (i2d_X509_REQ_bio(certificateSigningRequestBio, certificateSigningRequest) <= 0)
    {
        ERR_print_errors_fp(stderr);
        if (certificateSigningRequestBio)
            BIO_free(certificateSigningRequestBio);
        return NULL;
    }

    BIO_flush(certificateSigningRequestBio);
    BIO_set_flags(certificateSigningRequestBio, BIO_FLAGS_MEM_RDONLY);

    BIO_reset(certificateSigningRequestBio);

    // Prepare stack of receiver certificates
    if ((stackOfCertificates = sk_X509_new(NULL)) == NULL)
    {
        if (certificateSigningRequestBio)
            BIO_free(certificateSigningRequestBio);
        return NULL;
    }

    sk_X509_push(stackOfCertificates, receiverCertificate);

    // Create enveloped and encrypted PKCS7 structure containing signed certificate
    pkcs7EnvelopedAndEncryptedWithCertificateSigningRequest = CMS_encrypt(stackOfCertificates, certificateSigningRequestBio, cipher, CMS_BINARY);

    // Free resources
    if (stackOfCertificates)
        sk_X509_free(stackOfCertificates);

    if (certificateSigningRequestBio)
        BIO_free(certificateSigningRequestBio);

    // Return final PKCS7 structure
    return pkcs7EnvelopedAndEncryptedWithCertificateSigningRequest;
}

/* Converts a SCEP_MSG to a PKCS7 structure */
CMS_ContentInfo *getSignedScepMessage(
    X509 *signingCertificate,
    EVP_PKEY *signingKey,
    CMS_ContentInfo *pkcs7EnvelopedAndEncryptedWithCertificateSigningRequest,
    STACK_OF(X509_ATTRIBUTE) * scepAttributes)
{
    // Block Input/Output Object for PKCS7 structure with enveloped and encrypted signed certificate
    BIO *pkcs7EnvelopedAndEncryptedWithCertificateSigningRequestBio = NULL;

    // Final signed SCEP message PKCS7 structure
    CMS_ContentInfo *signedScepMessage = NULL;

    // Signer Info structure for adding SCEP attributes (which have to be signed)
    CMS_SignerInfo *signerInfo = NULL;

    // Prepare BIO for PKCS7 structure with enveloped and encrypted signed certificate
    if ((pkcs7EnvelopedAndEncryptedWithCertificateSigningRequestBio = BIO_new(BIO_s_mem())) == NULL)
    {
        goto err;
    }

    if ((pkcs7EnvelopedAndEncryptedWithCertificateSigningRequest != NULL) && (i2d_CMS_bio(pkcs7EnvelopedAndEncryptedWithCertificateSigningRequestBio, pkcs7EnvelopedAndEncryptedWithCertificateSigningRequest) <= 0))
    {
        goto err;
    }

    // Initialize final signed SCEP message PKCS7 structure
    signedScepMessage = CMS_sign(signingCertificate, signingKey, NULL, NULL, CMS_PARTIAL);

    // Extract signer info from signedScepMessage
    signerInfo = sk_CMS_SignerInfo_value(CMS_get0_SignerInfos(signedScepMessage), 0);
    if (!signerInfo)
    {
        goto err;
    }

    // Add SCEP attributes to be included and signed in the final SCEP message
    for (int i = 0; i < sk_X509_ATTRIBUTE_num(scepAttributes); i++)
    {
        X509_ATTRIBUTE *attr = sk_X509_ATTRIBUTE_value(scepAttributes, i);
        CMS_signed_add1_attr(signerInfo, attr);
    }

    // Finalize signed SCEP message PKCS7 structure - sign the structure
    CMS_final(signedScepMessage, pkcs7EnvelopedAndEncryptedWithCertificateSigningRequestBio, NULL, CMS_BINARY);

    if (pkcs7EnvelopedAndEncryptedWithCertificateSigningRequestBio)
        BIO_free(pkcs7EnvelopedAndEncryptedWithCertificateSigningRequestBio);

    return signedScepMessage;

err:
    ERR_print_errors_fp(stderr);
    if (pkcs7EnvelopedAndEncryptedWithCertificateSigningRequestBio)
        BIO_free(pkcs7EnvelopedAndEncryptedWithCertificateSigningRequestBio);
    if (signedScepMessage)
        CMS_ContentInfo_free(signedScepMessage);
    return (NULL);
}

// Write final signed SCEP message to output PEM file
int writeFinalSignedScepMessageToPemFile(BIO *outputFileBio, CMS_ContentInfo *signedScepMessage)
{
    BIO *base64BioFilter = NULL;
    BIO *base64Bio = NULL;

    // Write PEM headers
    BIO_printf(outputFileBio, "-----BEGIN SCEP MESSAGE-----\n");

    // Prepare base64 BIO filter for output SCEP message in PEM format
    if (!(base64BioFilter = BIO_new(BIO_f_base64())))
    {
        return 1;
    }
    base64Bio = BIO_push(base64BioFilter, outputFileBio);

    i2d_CMS_bio(base64Bio, signedScepMessage);
    BIO_flush(base64Bio);
    BIO_free(base64Bio);

    // Write PEM footers
    BIO_printf(outputFileBio, "-----END SCEP MESSAGE-----\n");

    return 0;
}

/**
 * scep <selfSign.crt> <selfSign.key> <ca.public.key.pem> <csr> <outPEM file>
 * */
int main(int argc, char **argv)
{
    BIO *outputFileBio = NULL, *temporaryBlockInputOutputObject = NULL;
    // In this case self-signed certificate
    X509 *signingCertificate = NULL;
    // In this case self-signed private key
    EVP_PKEY *signingKey = NULL;
    // CA certificate - to use as receiver certificate for encryption of signed certificate
    X509 *caCertificate = NULL;
    // PKCS7 structure containing enveloped and encrypted certificate signing request
    CMS_ContentInfo *pkcs7EnvelopedAndEncryptedWithCertificateSigningRequest = NULL;

    // SCEP attributes to be included in the signed message
    STACK_OF(X509_ATTRIBUTE) *scepAttributes = NULL;

    // Certificate signing request to be signed, enveloped and encrypted and put into SCEP message
    X509_REQ *certificateSigningRequest = NULL;

    // PKCS7 structure containing final signed SCEP message
    CMS_ContentInfo *signedScepMessage = NULL;

    // Return value
    int returnValue = 1;

    // default config
    const EVP_MD *hashalg = EVP_md5();
    const EVP_CIPHER *cipher = EVP_des_ede3_cbc();

    // *******
    // Check arguments
    if (argc != 6)
    {
        fprintf(stderr, "Usage: %s <selfSign.crt> <selfSign.key> <ca.public.key.pem> <csr> <outPEM file>\n", argv[0]);
        return 1;
    }

    // *******
    // Initialize OpenSSL
    OpenSSL_add_all_algorithms();
    ERR_load_crypto_strings();

    temporaryBlockInputOutputObject = BIO_new_file(argv[1], "r");
    if (!temporaryBlockInputOutputObject)
        goto err;
    signingCertificate = PEM_read_bio_X509(temporaryBlockInputOutputObject, NULL, 0, NULL);
    BIO_free(temporaryBlockInputOutputObject);
    temporaryBlockInputOutputObject = NULL;

    temporaryBlockInputOutputObject = BIO_new_file(argv[2], "r");
    if (!temporaryBlockInputOutputObject)
        goto err;
    signingKey = PEM_read_bio_PrivateKey(temporaryBlockInputOutputObject, NULL, 0, NULL);
    BIO_free(temporaryBlockInputOutputObject);
    temporaryBlockInputOutputObject = NULL;

    temporaryBlockInputOutputObject = BIO_new_file(argv[3], "r");
    if (!temporaryBlockInputOutputObject)
        goto err;
    caCertificate = PEM_read_bio_X509(temporaryBlockInputOutputObject, NULL, NULL, NULL);
    BIO_free(temporaryBlockInputOutputObject);
    temporaryBlockInputOutputObject = NULL;

    temporaryBlockInputOutputObject = BIO_new_file(argv[4], "r");
    if (!temporaryBlockInputOutputObject)
        goto err;
    certificateSigningRequest = PEM_read_bio_X509_REQ(temporaryBlockInputOutputObject, NULL, NULL, NULL);
    BIO_free(temporaryBlockInputOutputObject);
    temporaryBlockInputOutputObject = NULL;

    // Open output file for writing final SCEP message
    outputFileBio = BIO_new_file(argv[5], "w");
    if (!outputFileBio)
        goto err;

    scepAttributes = generateScepAttributes();

    pkcs7EnvelopedAndEncryptedWithCertificateSigningRequest = getPkcs7EnvelopedAndEncryptedWithCertificateSigningRequest(caCertificate, certificateSigningRequest, cipher);

    if (!pkcs7EnvelopedAndEncryptedWithCertificateSigningRequest)
        goto err;

    // Create final signed SCEP message
    signedScepMessage = getSignedScepMessage(
        signingCertificate,
        signingKey,
        pkcs7EnvelopedAndEncryptedWithCertificateSigningRequest,
        scepAttributes);

    if (!signedScepMessage)
        goto err;

    // Write final signed SCEP message to output PEM file
    returnValue = writeFinalSignedScepMessageToPemFile(outputFileBio, signedScepMessage);

err:
    if (returnValue)
    {
        fprintf(stderr, "Error Signing Data\n");
        ERR_print_errors_fp(stderr);
    }

    if (temporaryBlockInputOutputObject)
        BIO_free(temporaryBlockInputOutputObject);
    if (signingCertificate)
        X509_free(signingCertificate);
    if (signingKey)
        EVP_PKEY_free(signingKey);
    if (outputFileBio)
        BIO_free(outputFileBio);
    if (caCertificate)
        X509_free(caCertificate);
    if (certificateSigningRequest)
        X509_REQ_free(certificateSigningRequest);
    if (pkcs7EnvelopedAndEncryptedWithCertificateSigningRequest)
        CMS_ContentInfo_free(pkcs7EnvelopedAndEncryptedWithCertificateSigningRequest);
    if (scepAttributes)
        sk_X509_ATTRIBUTE_pop_free(scepAttributes, X509_ATTRIBUTE_free);

    ERR_clear_error();

    return returnValue;
}