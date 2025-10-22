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

int B64_write_bio_PKCS7(BIO *bio, PKCS7 *p7)
{
    BIO *b64 = NULL;
    int ret = 0;

    if (!p7)
        return 0;

    if (!(b64 = BIO_new(BIO_f_base64())))
    {
        return 0;
    }

    bio = BIO_push(b64, bio);
    ret = i2d_PKCS7_bio(bio, p7);
    BIO_flush(bio);
    bio = BIO_pop(bio);
    BIO_free(b64);

    return ret;
}

/* Converts a SCEP_MSG to a PKCS7 structure */
PKCS7 *i2pk7_SCEP_MSG(X509 *signingCertificate, EVP_PKEY *signingKey, PKCS7 *p7env, STACK_OF(X509_ATTRIBUTE) * attrs, const EVP_MD *hashalg) // SCEP_MSG *msg, EVP_PKEY *pkey)
{

    BIO *bio = NULL;
    PKCS7 *p7 = NULL;
    PKCS7_SIGNER_INFO *si = NULL;
    PKCS7_SIGNER_INFO *msg_si = NULL;

    STACK_OF(X509) *sk_others = NULL;
    X509 *x509 = NULL;

    int i = 0;
    long data_len = 0;
    unsigned char *data = NULL;

    BIO *debug_bio = NULL;

    if ((debug_bio = BIO_new(BIO_s_file())) != NULL)
        BIO_set_fp(debug_bio, stderr, BIO_NOCLOSE | BIO_FP_TEXT);

    /* Create the new p7 structure and set to signed */
    if ((p7 = PKCS7_new()) == NULL)
        goto err;
    if (!PKCS7_set_type(p7, NID_pkcs7_signed))
        goto err;
    if (!PKCS7_content_new(p7, NID_pkcs7_data))
        goto err;

    /* Add the signer certificate */
    PKCS7_add_certificate(p7, signingCertificate);

    if ((si = PKCS7_add_signature(p7, signingCertificate,
                                  signingKey, hashalg)) == NULL)
        goto err;

    if ((bio = BIO_new(BIO_s_mem())) == NULL)
        goto err;

    if ((p7env != NULL) && (i2d_PKCS7_bio(bio, p7env) > 0))
    {
        ERR_print_errors_fp(stderr);
        BIO_flush(bio);
        BIO_set_flags(bio, BIO_FLAGS_MEM_RDONLY);
        data_len = BIO_get_mem_data(bio, &data);
    }
    ERR_print_errors_fp(stderr);

    /* Add signed attributes */
    PKCS7_set_signed_attributes(si, attrs);

    PKCS7_add_signed_attribute(si, NID_pkcs9_contentType,
                               V_ASN1_OBJECT, OBJ_nid2obj(NID_pkcs7_data));

    /* Add data to the p7 file */
    if ((bio = PKCS7_dataInit(p7, NULL)) == NULL)
        goto err;

    if (data_len > 0)
    {
        BIO_write(bio, data, data_len);
    }

    ERR_print_errors_fp(stderr);

    /* Finalize signature */
    PKCS7_dataFinal(p7, bio);

    if (p7->d.sign->contents->d.data == NULL)
    {
        printf("is null\n");
    }

    return p7;

err:
    if (bio)
        BIO_free(bio);
    if (p7)
        PKCS7_free(p7);
    return (NULL);
}

int PEM_write_bio_SCEP_MSG(BIO *bio, X509 *signingCertificate, EVP_PKEY *signingKey, PKCS7 *p7env, STACK_OF(X509_ATTRIBUTE) * attrs, const EVP_MD *hashalg)
{

    PKCS7 *p7 = NULL;
    int ret = 0;

    /* Generate the signed pkcs7 message */
    if ((p7 = i2pk7_SCEP_MSG(signingCertificate, signingKey, p7env, attrs, hashalg)) == NULL)
        return 0;

    BIO_printf(bio, "-----BEGIN SCEP MESSAGE-----\n");
    ret = B64_write_bio_PKCS7(bio, p7);
    BIO_printf(bio, "-----END SCEP MESSAGE-----\n");
    // BIO_printf(bio, "-----BEGIN PKCS7-----\n");
    // ret = B64_write_bio_PKCS7(bio, p7);
    // BIO_printf(bio, "-----END PKCS7-----\n");
    PKCS7_free(p7);

    ERR_clear_error();

    return ret;
}

/**
 * scep <selfSign.crt> <selfSign.key> <ca.public.key.pem> <csr> <outPEM file>
 * */
int main(int argc, char **argv)
{
    BIO *encyptedCsrBio = NULL, *out = NULL, *tbio = NULL;
    X509 *signingCertificate = NULL;
    X509 *ca = NULL;
    EVP_PKEY *signingKey = NULL;
    PKCS7 *cms = NULL;
    PKCS7 *p7env = NULL; // PKCS7 Envelope
    PKCS7_SIGNER_INFO *si = NULL;
    X509_REQ *csr;
    STACK_OF(X509) *sk = NULL;
    STACK_OF(PKCS7_RECIP_INFO) *sk_recip_info = NULL;
    STACK_OF(X509_ATTRIBUTE) *attrs = NULL;
    X509_ATTRIBUTE *attr = NULL;
    STACK_OF(PKCS7_SIGNER_INFO) * sk_signer_info;
    PKCS7_ISSUER_AND_SERIAL *signer_ias;
    int ret = 1;
    int NID_p7data;
    // default config
    const EVP_MD *hashalg = EVP_md5();
    const EVP_CIPHER *cipher = EVP_des_ede3_cbc();

    OpenSSL_add_all_algorithms();
    ERR_load_crypto_strings();

    tbio = BIO_new_file(argv[1], "r");
    if (!tbio)
        goto err;
    signingCertificate = PEM_read_bio_X509(tbio, NULL, 0, NULL);
    BIO_free(tbio);

    tbio = BIO_new_file(argv[2], "r");
    if (!tbio)
        goto err;
    signingKey = PEM_read_bio_PrivateKey(tbio, NULL, 0, NULL);

    BIO_free(tbio);
    tbio = BIO_new_file(argv[3], "r");
    if (!tbio)
        goto err;
    ca = PEM_read_bio_X509(tbio, NULL, NULL, NULL);

    BIO_free(tbio);
    tbio = BIO_new_file(argv[4], "r");
    if (!tbio)
        goto err;
    csr = (X509_REQ *)PEM_read_bio_X509_REQ(tbio, NULL, NULL, NULL);

    // OUT
    out = BIO_new_file(argv[5], "w");
    if (!out)
        goto err;
    // Loading files

    attrs = sk_X509_ATTRIBUTE_new_null();
    NID_p7data = NID_pkcs7_signedAndEnveloped;
    sk_signer_info = sk_PKCS7_SIGNER_INFO_new_null();

    if ((si = PKCS7_SIGNER_INFO_new()) == NULL)
        goto err;

    if (!ASN1_INTEGER_set(si->version, 1))
        goto err;
    if (!X509_NAME_set(&si->issuer_and_serial->issuer,
                       X509_get_issuer_name(signingCertificate)))
        goto err;

    ASN1_INTEGER_free(si->issuer_and_serial->serial);
    if (!(si->issuer_and_serial->serial =
              ASN1_INTEGER_dup(X509_get_serialNumber(signingCertificate))))
        goto err;

    si->pkey = signingKey;

    X509_ALGOR_set0(si->digest_alg, OBJ_nid2obj(EVP_MD_type(hashalg)),
                    V_ASN1_NULL, NULL);

    sk_PKCS7_SIGNER_INFO_push(sk_signer_info, si);
    signer_ias = si->issuer_and_serial;

    if ((sk = sk_X509_new(NULL)) == NULL)
        goto err;

    sk_X509_push(sk, ca);

    BIO *inbio = NULL;
    inbio = BIO_new(BIO_s_mem());
    if (i2d_X509_REQ_bio(inbio, csr) <= 0)
        goto err;

    BIO_flush(inbio);
    BIO_set_flags(inbio, BIO_FLAGS_MEM_RDONLY);

    p7env = PKCS7_encrypt(sk, inbio, cipher, PKCS7_BINARY);

    if (inbio)
        BIO_free(inbio);

    int messageTypeNid = OBJ_create("2.16.840.1.113733.1.9.2", "messageType", "Message Type");
    ASN1_STRING *messageTypeValue = ASN1_STRING_new();
    ASN1_STRING_set(messageTypeValue, "19", 2);
    attr = X509_ATTRIBUTE_create(messageTypeNid, V_ASN1_PRINTABLESTRING, messageTypeValue);
    sk_X509_ATTRIBUTE_push(attrs, attr);

    int pkiStatusNid = OBJ_create("2.16.840.1.113733.1.9.3", "pkiStatus", "PKI Status");
    ASN1_STRING *pkiStatusValue = ASN1_STRING_new();
    ASN1_STRING_set(pkiStatusValue, "3", 1);

    attr = X509_ATTRIBUTE_create(pkiStatusNid, V_ASN1_PRINTABLESTRING, pkiStatusValue);
    sk_X509_ATTRIBUTE_push(attrs, attr);

    char transactionId[17];
    rand_str(transactionId, 16);
    int transactionIDNid = OBJ_create("2.16.840.1.113733.1.9.7", "transactionID", "transactionID");
    ASN1_STRING *transactionIDValue = ASN1_STRING_new();
    ASN1_STRING_set(transactionIDValue, transactionId, 16);

    attr = X509_ATTRIBUTE_create(transactionIDNid, V_ASN1_PRINTABLESTRING, transactionIDValue);
    sk_X509_ATTRIBUTE_push(attrs, attr);

    char senderNonce[17];
    rand_str(senderNonce, 16);
    int senderNonceNid = OBJ_create("2.16.840.1.113733.1.9.5", "senderNonce", "senderNonce");
    ASN1_OCTET_STRING *senderNonceValue = ASN1_OCTET_STRING_new();
    ASN1_OCTET_STRING_set(senderNonceValue, senderNonce, 16);

    attr = X509_ATTRIBUTE_create(senderNonceNid, V_ASN1_OCTET_STRING, senderNonceValue);
    sk_X509_ATTRIBUTE_push(attrs, attr);

    char recipientNonce[17];
    rand_str(recipientNonce, 16);
    int recipientNonceNid = OBJ_create("2.16.840.1.113733.1.9.6", "recipientNonce", "recipientNonce");
    ASN1_OCTET_STRING *recipientNonceValue = ASN1_OCTET_STRING_new();
    ASN1_OCTET_STRING_set(recipientNonceValue, recipientNonce, 16);

    attr = X509_ATTRIBUTE_create(recipientNonceNid, V_ASN1_OCTET_STRING, recipientNonceValue);
    sk_X509_ATTRIBUTE_push(attrs, attr);

    PEM_write_bio_SCEP_MSG(out, signingCertificate, signingKey, p7env, attrs, hashalg);

    ret = 0;
err:
    if (ret)
    {
        fprintf(stderr, "Error Signing Data\n");
        ERR_print_errors_fp(stderr);
    }
    if (cms)
        PKCS7_free(cms);
    if (signingCertificate)
        X509_free(signingCertificate);
    if (signingKey)
        EVP_PKEY_free(signingKey);
    if (encyptedCsrBio)
        BIO_free(encyptedCsrBio);
    if (out)
        BIO_free(out);

    return ret;
}