library(immunarch)
library(xtable)

# test for arguments
args = commandArgs(trailingOnly=TRUE)
if (length(args)!=2) {
  stop("An input and output directory must be supplied.n", call.=FALSE)
}

# Set our error handling variable
ir_error_count <- 0

# Get the input and output directories
input_dir <- args[1]
output_dir <- args[2]

# Load data. Need a separate tsv per repertoire. Metadata file is optional,
# but allows for grouped comparisons.
data <- repLoad(input_dir)

### exploratory analysis. The repExplore function calculates the basic statistics of repertoire: the number of unique immune receptor clonotypes, their relative abundances, and sequence length distribution across the input dataset.
# repExplore(.data, .method = c("volume", "count", "len", "clones"), .col = c("nt", "aa"), .coding = TRUE)

# number of unique clonotypes (rearrangements??)
result = tryCatch(
{
    exp_vol <- repExplore(data$data, .method = "volume")
    exp_vol_vis <- vis(exp_vol)
    ggsave(paste(output_dir, "vol.png", sep="/"), plot = exp_vol_vis, device = "png")
}, error = function(err)
{
    print(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})

# distribution of clonotype abundances, i.e., how frequent receptors with different abundances are
result = tryCatch(
{
    exp_count <- repExplore(data$data, .method = "count")
    exp_count_vis <- vis(exp_count)
    ggsave(paste(output_dir, "count.png", sep="/"), plot = exp_count_vis, device = "png")
}, error = function(err)
{
    cat(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})

cat(paste("result = ", result, "\n"))
cat(paste("error = ", ir_error_count, "\n"))

# Distribution of CDR3 sequence lengths
result = tryCatch(
{
    exp_len <- repExplore(data$data, .method = "len", .col="aa")
    #exp_len <- repExplore(data$data, .method = "len")
    exp_len_vis <- vis(exp_len)
    ggsave(paste(output_dir, "len.png", sep="/"), plot = exp_len_vis, device = "png")
}, error = function(err)
{
    cat(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})

cat(paste("result = ", result, "\n"))
cat(paste("error = ", ir_error_count, "\n"))
  
# Top 10 clones
result = tryCatch(
{
    print('top_10_clones.tsv')
    top_10 <- top(data$data[[1]], .n = 10)
    #top_10 <- top(data$data, .n = 10)
    write.table(top_10, file = paste(output_dir, "top_10_clones.tsv", sep="/"), sep = "\t",row.names=FALSE)
    print(xtable(top_10[0:10,c(1,2,4,5,7)]), type = "html", file=paste(output_dir, "top_10_clones.html", sep="/"))
}, error = function(err)
{
    cat(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})

# number of clones (i.e., cells) per input repertoire
result = tryCatch(
{
    print('clones.png')
    exp_clon <- repExplore(data$data, .method = "clones")
    exp_clon_vis <- vis(exp_clon)
    ggsave(paste(output_dir, "clones.png", sep="/"), plot = exp_clon_vis, device = "png")
}, error = function(err)
{
    cat(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})

### Clonality analyis. repClonality function encompasses several methods to measure clonal proportions in a given repertoire.
# repClonality(.data,.method = c("clonal.prop", "homeo", "top", "rare"), .perc = 10, .clone.types = c(Rare = 1e-05, Small = 1e-04, Medium = 0.001, Large = 0.01, Hyperexpanded = 1), .head = c(10, 100, 1000, 3000, 10000, 30000, 1e+05), .bound = c(1, 3, 10, 30, 100))

# clonal proportions or in other words percentage of clonotypes required to occupy specified by .perc percent of the total immune repertoire.
result = tryCatch(
{
    print("clonal_prop.png")
    clon_prop <- repClonality(data$data, .method = "clonal.prop")
    clon_prop_vis <- vis(clon_prop)
    ggsave(paste(output_dir, "clonal_prop.png", sep="/"), plot = clon_prop_vis, device = "png")
}, error = function(err)
{
    cat(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})

# estimate relative abundance for the groups of top clonotypes in repertoire, e.g., ten most abundant clonotypes. Use ".head" to define index intervals, such as 10, 100 and so on.
#print("clonal_top.png")
#clon_top <- repClonality(data$data, .method = "top")
#clon_top_vis <- vis(clon_top)
#ggsave(paste(output_dir, "clonal_top.png", sep="/"), plot = clon_top_vis, device = "png")

# relative abundance for the groups of rare clonotypes with low counts. Use ".bound" to define the threshold of clonotype groups.
result = tryCatch(
{
    print("clonal_rare.png")
    clon_rare <- repClonality(data$data, .method = "rare")
    clon_rare_vis <- vis(clon_rare)
    ggsave(paste(output_dir, "clonal_rare.png", sep="/"), plot = clon_rare_vis, device = "png")
}, error = function(err)
{
    cat(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})

# relative abundance (also known as clonal space homeostasis), which is defined as the proportion of repertoire occupied by clonal groups with specific abundances.
result = tryCatch(
{
    print("clonal_homeo.png")
    clon_hom <- repClonality(data$data, .method = "homeo")
    clon_hom_vis <- vis(clon_hom)
    ggsave(paste(output_dir, "clonal_homeo.png", sep="/"), plot = clon_hom_vis, device = "png")
}, error = function(err)
{
    cat(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})

### diversity analysis. Estimate the diversity of species or objects in the given distribution
# repDiversity(.data, .method = "chao1", .col = "aa", .max.q = 6, .min.q = 1, .q = 5, .step = NA, .quantile = c(0.025, 0.975), .extrapolation = NA, .perc = 50, .norm = TRUE, .verbose = TRUE, do.norm = NA, .laplace = 0)

# chao1
result = tryCatch(
{
    print("div_chao.png")
    div_chao <- repDiversity(data$data, .method = "chao1")
    div_chao_vis <- vis(div_chao)
    ggsave(paste(output_dir, "div_chao.png", sep="/"), plot = div_chao_vis, device = "png")
}, error = function(err)
{
    cat(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})

# hill
result = tryCatch(
{
    print("div_hill.png")
    div_hill <- repDiversity(data$data, .method = "hill")
    div_hill_vis <- vis(div_hill)
    ggsave(paste(output_dir, "div_hill.png", sep="/"), plot = div_hill_vis, device = "png")
}, error = function(err)
{
    cat(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})

# true diversity
result = tryCatch(
{
    print("div_true-div.png")
    div_trdiv <- repDiversity(data$data, .method = "div")
    div_trdiv_vis <- vis(div_trdiv)
    ggsave(paste(output_dir, "div_true-div.png", sep="/"), plot = div_trdiv_vis, device = "png")
}, error = function(err)
{
    cat(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})

# gini-simpson
result = tryCatch(
{
    print("div_gini-simpson.png")
    div_ginsimp <- repDiversity(data$data, .method = "gini.simp")
    div_ginsimp_vis <- vis(div_ginsimp)
    ggsave(paste(output_dir, "div_gini-simpson.png", sep="/"), plot = div_ginsimp_vis, device = "png")
}, error = function(err)
{
    cat(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})

# inverse simpson
result = tryCatch(
{
    print("div_inverse-simpson.png")
    div_invsimp <- repDiversity(data$data, .method = "inv.simp")
    div_invsimp_vis <- vis(div_invsimp)
    ggsave(paste(output_dir, "div_inverse-simpson.png", sep="/"), plot = div_invsimp_vis, device = "png")
}, error = function(err)
{
    cat(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})

# d50
result = tryCatch(
{
    print("div_d50.png")
    div_d50 <- repDiversity(data$data, .method = "d50")
    div_d50_vis <- vis(div_d50)
    ggsave(paste(output_dir, "div_d50.png", sep="/"), plot = div_d50_vis, device = "png")
}, error = function(err)
{
    cat(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})

### gene usage. 
# geneUsage(.data, .gene = c("hs.trbv", "HomoSapiens.TRBJ", "macmul.IGHV"), .quant = c(NA, "count"), .ambig = c("inc", "exc", "maj"), .type = c("segment", "allele", "family"), .norm = FALSE)

# gene segment usage, normalized
result = tryCatch(
{
    print("gene_usage_normalized.png")
    gu_norm <- geneUsage(data$data, .norm = T)
    gu_norm_vis <- vis(gu_norm)
    ggsave(paste(output_dir, "gene_usage_normalized.png", sep="/"), plot = gu_norm_vis, device = "png")
}, error = function(err)
{
    cat(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})

# gene family usage, normalized
result = tryCatch(
{
    print("gene_family_usage_normalized.png")
    gu_fam_norm <- geneUsage(data$data, .norm = T, .type = "family")
    gu_fam_norm_vis <- vis(gu_fam_norm)
    ggsave(paste(output_dir, "gene_family_usage_normalized.png", sep="/"), plot = gu_fam_norm_vis, device = "png")
}, error = function(err)
{
    cat(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})

### overlap analysis

# jaccard index. Measures the similarity between finite sample sets, and is defined as the size of the intersection divided by the size of the union of the sample sets.
result = tryCatch(
{
    print("overlap-jaccard.png")
    ov_jacc <- repOverlap(data$data, .method = "jaccard", .verbose = FALSE)
    ov_jacc_vis <- vis(ov_jacc)
    ggsave(paste(output_dir, "overlap-jaccard.png", sep="/"), plot = ov_jacc_vis, device = "png")
}, error = function(err)
{
    cat(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})

# overlap coefficient. A normalised measure of overlap similarity. It is defined as the size of the intersection divided by the smaller of the size of the two sets.
result = tryCatch(
{
    print("overlap.png")
    ov <- repOverlap(data$data, .method = "overlap", .verbose = FALSE)
    ov_vis <- vis(ov)
    ggsave(paste(output_dir, "overlap.png", sep="/"), plot = ov_vis, device = "png")
}, error = function(err)
{
    cat(paste("IR-ERROR:  ",err, "\n"))
    ir_error_count <<- ir_error_count + 1
})
